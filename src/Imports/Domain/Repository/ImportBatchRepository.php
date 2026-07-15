<?php

declare(strict_types=1);

namespace App\Imports\Domain\Repository;

use App\Imports\Domain\Entity\ImportBatch;
use App\Imports\Domain\Enum\ImportBatchStatus;
use App\Imports\Domain\Enum\ImportType;
use App\Organizations\Domain\Entity\Organization;

interface ImportBatchRepository
{
    public function save(Organization $organization, ImportBatch $batch): void;

    public function findById(Organization $organization, int $id): ?ImportBatch;

    public function findByIdForUpdate(Organization $organization, int $id): ?ImportBatch;

    public function findCompletedByHash(Organization $organization, ImportType $type, string $hash): ?ImportBatch;

    /** @return list<ImportBatch> */
    public function list(Organization $organization, ?ImportType $type, ?ImportBatchStatus $status, int $page, int $perPage): array;

    public function countMatching(Organization $organization, ?ImportType $type, ?ImportBatchStatus $status): int;
}
