<?php

declare(strict_types=1);

namespace App\Imports\Domain\Repository;

use App\Imports\Domain\Entity\ImportBatch;
use App\Imports\Domain\Entity\ImportRow;
use App\Imports\Domain\Enum\ImportRowStatus;
use App\Organizations\Domain\Entity\Organization;

interface ImportRowRepository
{
    public function save(Organization $organization, ImportRow $row): void;

    public function deleteByBatch(Organization $organization, ImportBatch $batch): void;

    /** @return list<ImportRow> */
    public function list(Organization $organization, ImportBatch $batch, int $page, int $perPage, ?ImportRowStatus $status = null): array;

    /** @return list<ImportRow> */
    public function findValidForProcessing(Organization $organization, ImportBatch $batch): array;

    public function countMatching(Organization $organization, ImportBatch $batch, ?ImportRowStatus $status = null): int;

    /** @return list<ImportRow> */
    public function listErrors(Organization $organization, ImportBatch $batch, int $page, int $perPage): array;

    public function countErrors(Organization $organization, ImportBatch $batch): int;
}
