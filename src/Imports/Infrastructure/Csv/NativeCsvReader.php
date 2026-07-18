<?php

declare(strict_types=1);

namespace App\Imports\Infrastructure\Csv;

use App\Imports\Application\DTO\CsvInspection;
use App\Imports\Application\DTO\CsvRow;
use App\Imports\Application\Port\CsvReaderInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class NativeCsvReader implements CsvReaderInterface
{
    public function __construct(private int $maxRows = 2_000, private int $maxColumns = 50, private int $maxCellBytes = 10_240)
    {
    }

    public function inspect($stream): CsvInspection
    {
        $this->assertStream($stream);
        rewind($stream);
        $sample = fread($stream, 65_536);
        if (false === $sample || '' === $sample) {
            throw new DomainException('IMPORT_EMPTY_FILE', 'O arquivo CSV está vazio.', 422, 'file');
        }
        if (str_contains($sample, "\0")) {
            throw new DomainException('IMPORT_INVALID_FILE', 'O arquivo contém dados binários.', 422, 'file');
        }
        [$encoding, $utf8] = $this->toUtf8($sample);
        $firstLine = strtok($utf8, "\r\n");
        if (false === $firstLine || '' === trim($firstLine)) {
            throw new DomainException('IMPORT_INVALID_FILE', 'Cabeçalho CSV ausente.', 422, 'file');
        }
        $delimiter = $this->detectDelimiter($firstLine);
        $headers = str_getcsv($firstLine, $delimiter, '"', '');
        $headers = array_map(static fn (?string $header): string => trim((string) $header), $headers);
        if ([] === $headers || count($headers) > $this->maxColumns || in_array('', $headers, true) || count($headers) !== count(array_unique($headers))) {
            throw new DomainException('IMPORT_INVALID_FILE', 'O cabeçalho CSV é inválido ou duplicado.', 422, 'file');
        }
        $this->assertWellFormed($stream, $delimiter, $encoding);

        return new CsvInspection(array_values($headers), $encoding, $delimiter);
    }

    /**
     * @param resource $stream
     *
     * @return iterable<CsvRow>
     */
    public function rows($stream, CsvInspection $inspection): iterable
    {
        $this->assertStream($stream);
        rewind($stream);
        $filter = null;
        if ('WINDOWS-1252' === $inspection->encoding) {
            $filter = stream_filter_append($stream, 'convert.iconv.CP1252/UTF-8', \STREAM_FILTER_READ);
        } elseif ('UTF-8-BOM' === $inspection->encoding) {
            fseek($stream, 3);
        }
        try {
            $header = fgetcsv($stream, null, $inspection->delimiter, '"', '');
            if (false === $header) {
                throw new DomainException('IMPORT_INVALID_FILE', 'Cabeçalho CSV ausente.', 422, 'file');
            }
            $rowNumber = 1;
            while (false !== ($values = fgetcsv($stream, null, $inspection->delimiter, '"', ''))) {
                ++$rowNumber;
                if ($rowNumber - 1 > $this->maxRows) {
                    throw new DomainException('IMPORT_TOO_MANY_ROWS', 'O arquivo excede 2.000 linhas de dados.', 422, 'file');
                }
                if ([null] === $values || (1 === count($values) && '' === trim((string) $values[0]))) {
                    continue;
                }
                if (count($values) !== count($inspection->headers) || count($values) > $this->maxColumns) {
                    throw new DomainException('IMPORT_INVALID_FILE', sprintf('A linha %d possui quantidade inválida de colunas.', $rowNumber), 422, 'file');
                }
                $data = [];
                foreach ($inspection->headers as $index => $name) {
                    $value = null === $values[$index] ? null : (string) $values[$index];
                    if (null !== $value && strlen($value) > $this->maxCellBytes) {
                        throw new DomainException('IMPORT_CELL_TOO_LARGE', sprintf('A linha %d contém uma célula acima de 10 KB.', $rowNumber), 422, 'file');
                    }
                    $data[$name] = $value;
                }
                yield new CsvRow($rowNumber, $data);
            }
        } finally {
            if (is_resource($filter)) {
                stream_filter_remove($filter);
            }
        }
    }

    /** @return array{string, string} */
    private function toUtf8(string $sample): array
    {
        if (str_starts_with($sample, "\xEF\xBB\xBF")) {
            return ['UTF-8-BOM', substr($sample, 3)];
        }
        if (mb_check_encoding($sample, 'UTF-8')) {
            return ['UTF-8', $sample];
        }
        $converted = @iconv('CP1252', 'UTF-8', $sample);
        if (false === $converted) {
            throw new DomainException('IMPORT_UNSUPPORTED_ENCODING', 'Utilize UTF-8 ou Windows-1252.', 422, 'file');
        }

        return ['WINDOWS-1252', $converted];
    }

    private function detectDelimiter(string $header): string
    {
        $comma = count(str_getcsv($header, ',', '"', ''));
        $semicolon = count(str_getcsv($header, ';', '"', ''));
        if ($comma === $semicolon || max($comma, $semicolon) < 2) {
            throw new DomainException('IMPORT_INVALID_FILE', 'Não foi possível detectar um delimitador CSV não ambíguo.', 422, 'file');
        }

        return $comma > $semicolon ? ',' : ';';
    }

    /** @param resource $stream */
    private function assertWellFormed($stream, string $delimiter, string $encoding): void
    {
        rewind($stream);
        if ('UTF-8-BOM' === $encoding) {
            fseek($stream, 3);
        }
        $state = 'start';
        $line = 1;
        while (!feof($stream)) {
            $chunk = fread($stream, 8192);
            if (false === $chunk) {
                throw new DomainException('IMPORT_INVALID_FILE', 'Não foi possível ler o CSV.', 422, 'file');
            }
            $length = strlen($chunk);
            for ($offset = 0; $offset < $length; ++$offset) {
                $character = $chunk[$offset];
                $newline = "\n" === $character || "\r" === $character;
                if ('quoted' === $state) {
                    if ('"' === $character) {
                        $state = 'after_quote';
                    }
                    if ($newline) {
                        ++$line;
                    }
                    continue;
                }
                if ('after_quote' === $state) {
                    if ('"' === $character) {
                        $state = 'quoted';
                    } elseif ($character === $delimiter || $newline) {
                        $state = 'start';
                    } else {
                        throw new DomainException('IMPORT_INVALID_FILE', sprintf('Aspas CSV inválidas próximas à linha %d.', $line), 422, 'file');
                    }
                    if ($newline) {
                        ++$line;
                    }
                    continue;
                }
                if ('start' === $state) {
                    if ('"' === $character) {
                        $state = 'quoted';
                    } elseif ($character !== $delimiter && !$newline) {
                        $state = 'unquoted';
                    }
                } elseif ('"' === $character) {
                    throw new DomainException('IMPORT_INVALID_FILE', sprintf('Aspas CSV inválidas próximas à linha %d.', $line), 422, 'file');
                } elseif ($character === $delimiter || $newline) {
                    $state = 'start';
                }
                if ($newline) {
                    ++$line;
                }
            }
        }
        if ('quoted' === $state) {
            throw new DomainException('IMPORT_INVALID_FILE', sprintf('Campo CSV com aspas não fechadas próximo à linha %d.', $line), 422, 'file');
        }
    }

    private function assertStream(mixed $stream): void
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('CSV reader expects a stream resource.');
        }
    }
}
