<?php

declare(strict_types=1);

namespace App\Imports\Application\DTO;

final readonly class CsvRow
{
    /** @param array<string, string|null> $data */
    public function __construct(public int $number, public array $data)
    {
    }
}
