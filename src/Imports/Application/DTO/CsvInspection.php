<?php

declare(strict_types=1);

namespace App\Imports\Application\DTO;

final readonly class CsvInspection
{
    /** @param list<string> $headers */
    public function __construct(public array $headers, public string $encoding, public string $delimiter)
    {
    }
}
