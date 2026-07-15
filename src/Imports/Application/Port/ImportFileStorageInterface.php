<?php

declare(strict_types=1);

namespace App\Imports\Application\Port;

use App\Imports\Application\DTO\StoredImportFile;

interface ImportFileStorageInterface
{
    public function store(string $temporaryPath, string $originalFileName): StoredImportFile;

    /** @return resource */
    public function open(string $storageKey);

    public function exists(string $storageKey): bool;

    public function remove(string $storageKey): void;
}
