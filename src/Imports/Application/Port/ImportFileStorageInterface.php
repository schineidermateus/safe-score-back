<?php

declare(strict_types=1);

namespace App\Imports\Application\Port;

use App\Imports\Application\DTO\StoredImportFile;

interface ImportFileStorageInterface
{
    public function store(int $organizationId, string $temporaryPath, string $originalFileName): StoredImportFile;

    /** @return resource */
    public function open(int $organizationId, string $storageKey);

    public function exists(int $organizationId, string $storageKey): bool;

    public function remove(int $organizationId, string $storageKey): void;
}
