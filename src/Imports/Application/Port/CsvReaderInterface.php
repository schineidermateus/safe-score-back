<?php

declare(strict_types=1);

namespace App\Imports\Application\Port;

use App\Imports\Application\DTO\CsvInspection;
use App\Imports\Application\DTO\CsvRow;

interface CsvReaderInterface
{
    /** @param resource $stream */
    public function inspect($stream): CsvInspection;

    /**
     * @param resource $stream
     *
     * @return iterable<CsvRow>
     */
    public function rows($stream, CsvInspection $inspection): iterable;
}
