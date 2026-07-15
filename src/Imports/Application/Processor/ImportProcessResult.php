<?php

declare(strict_types=1);

namespace App\Imports\Application\Processor;

final readonly class ImportProcessResult
{
    public function __construct(public string $entityType, public int $entityId, public bool $skipped)
    {
    }
}
