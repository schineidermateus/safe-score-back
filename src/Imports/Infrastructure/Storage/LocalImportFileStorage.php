<?php

declare(strict_types=1);

namespace App\Imports\Infrastructure\Storage;

use App\Imports\Application\DTO\StoredImportFile;
use App\Imports\Application\Port\ImportFileStorageInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class LocalImportFileStorage implements ImportFileStorageInterface
{
    public function __construct(private string $baseDirectory, private int $maxFileSize = 2_097_152)
    {
    }

    public function store(int $organizationId, string $temporaryPath, string $originalFileName): StoredImportFile
    {
        if (!is_file($temporaryPath) || !is_readable($temporaryPath)) {
            throw new DomainException('IMPORT_INVALID_FILE', 'Arquivo de importação inválido.', 422, 'file');
        }
        $size = filesize($temporaryPath);
        if (false === $size || 0 === $size) {
            throw new DomainException('IMPORT_EMPTY_FILE', 'O arquivo CSV está vazio.', 422, 'file');
        }
        if ($size > $this->maxFileSize) {
            throw new DomainException('IMPORT_FILE_TOO_LARGE', 'O arquivo excede o limite de 2 MB.', 413, 'file');
        }
        $extension = strtolower((string) pathinfo($originalFileName, \PATHINFO_EXTENSION));
        if ('csv' !== $extension) {
            throw new DomainException('IMPORT_INVALID_FILE', 'Somente arquivos CSV são aceitos.', 422, 'file');
        }
        if (class_exists(\finfo::class)) {
            $mime = (new \finfo(\FILEINFO_MIME_TYPE))->file($temporaryPath);
            if (false === $mime || !in_array($mime, ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'], true)) {
                throw new DomainException('IMPORT_INVALID_FILE', 'O conteúdo enviado não foi reconhecido como CSV textual.', 422, 'file');
            }
        }
        $original = $this->sanitizeOriginalName($originalFileName);
        $key = bin2hex(random_bytes(24)).'.csv';
        $this->ensureDirectory($organizationId);
        $destination = $this->path($organizationId, $key);
        if (!copy($temporaryPath, $destination)) {
            @unlink($destination);
            throw new DomainException('IMPORT_STORAGE_FAILED', 'Não foi possível armazenar o arquivo.', 500);
        }
        @chmod($destination, 0600);
        $hash = hash_file('sha256', $destination);
        if (false === $hash) {
            @unlink($destination);
            throw new DomainException('IMPORT_STORAGE_FAILED', 'Não foi possível verificar o arquivo.', 500);
        }

        return new StoredImportFile($key, $original, $key, $hash, $size);
    }

    public function open(int $organizationId, string $storageKey)
    {
        $path = $this->path($organizationId, $this->safeKey($storageKey));
        $stream = @fopen($path, 'r');
        if (false === $stream) {
            throw new DomainException('IMPORT_FILE_NOT_FOUND', 'Arquivo de importação não encontrado.', 404);
        }

        return $stream;
    }

    public function exists(int $organizationId, string $storageKey): bool
    {
        return is_file($this->path($organizationId, $this->safeKey($storageKey)));
    }

    public function remove(int $organizationId, string $storageKey): void
    {
        $path = $this->path($organizationId, $this->safeKey($storageKey));
        if (is_file($path) && !@unlink($path)) {
            throw new DomainException('IMPORT_STORAGE_FAILED', 'Não foi possível remover o arquivo.', 500);
        }
    }

    private function safeKey(string $key): string
    {
        if (1 !== preg_match('/\A[a-f0-9]{48}\.csv\z/', $key)) {
            throw new DomainException('IMPORT_INVALID_STORAGE_KEY', 'Chave de armazenamento inválida.', 422);
        }

        return $key;
    }

    private function path(int $organizationId, string $key): string
    {
        if ($organizationId < 1) {
            throw new DomainException('IMPORT_INVALID_TENANT', 'Organização inválida para armazenamento.', 422);
        }

        return rtrim($this->baseDirectory, '\\/').\DIRECTORY_SEPARATOR.$organizationId.\DIRECTORY_SEPARATOR.$key;
    }

    private function ensureDirectory(int $organizationId): void
    {
        $directory = rtrim($this->baseDirectory, '\\/').\DIRECTORY_SEPARATOR.$organizationId;
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new DomainException('IMPORT_STORAGE_FAILED', 'Diretório de importação indisponível.', 500);
        }
        if (!is_writable($directory)) {
            throw new DomainException('IMPORT_STORAGE_FAILED', 'Diretório de importação sem permissão de escrita.', 500);
        }
    }

    private function sanitizeOriginalName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
        $name = trim($name);

        return '' === $name ? 'import.csv' : mb_substr($name, 0, 255);
    }
}
