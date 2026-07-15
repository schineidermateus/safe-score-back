<?php

declare(strict_types=1);

namespace App\Imports\Application\DTO;

final readonly class StoredImportFile
{
    public function __construct(
        public string $fileName,
        public string $originalFileName,
        public string $storageKey,
        public string $hash,
        public int $size,
    ) {
    }
}
