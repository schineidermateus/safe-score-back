<?php

declare(strict_types=1);

namespace App\Tests\Imports\Support;

use App\Imports\Domain\Entity\ImportBatch;
use App\Imports\Domain\Enum\ImportBatchStatus;
use App\Imports\Domain\Enum\ImportType;
use App\Imports\Domain\Repository\ImportBatchRepository;
use App\Organizations\Domain\Entity\Organization;
use App\Tests\Support\EntityId;

final class InMemoryImportBatchRepository implements ImportBatchRepository
{
    /** @var array<int, ImportBatch> */ private array $items = [];

    public function save(Organization $organization, ImportBatch $batch): void
    {
        if ($batch->organization() !== $organization) {
            throw new \LogicException('Tenant mismatch.');
        } if (null === $batch->id()) {
            EntityId::assign($batch, count($this->items) + 1);
        } $this->items[$batch->requireId()] = $batch;
    }

    public function findById(Organization $organization, int $id): ?ImportBatch
    {
        $batch = $this->items[$id] ?? null;

        return null !== $batch && $batch->organization() === $organization ? $batch : null;
    }

    public function findByIdForUpdate(Organization $organization, int $id): ?ImportBatch
    {
        return $this->findById($organization, $id);
    }

    public function findCompletedByHash(Organization $organization, ImportType $type, string $hash): ?ImportBatch
    {
        foreach (array_reverse($this->items) as $batch) {
            if ($batch->organization() === $organization && $batch->type() === $type && $batch->fileHash() === $hash && in_array($batch->status(), [ImportBatchStatus::Completed, ImportBatchStatus::CompletedWithErrors], true)) {
                return $batch;
            }
        }

        return null;
    }

    public function list(Organization $organization, ?ImportType $type, ?ImportBatchStatus $status, int $page, int $perPage): array
    {
        return array_slice($this->filtered($organization, $type, $status), ($page - 1) * $perPage, $perPage);
    }

    public function countMatching(Organization $organization, ?ImportType $type, ?ImportBatchStatus $status): int
    {
        return count($this->filtered($organization, $type, $status));
    }

    /** @return list<ImportBatch> */
    private function filtered(Organization $organization, ?ImportType $type, ?ImportBatchStatus $status): array
    {
        return array_values(array_filter($this->items, static fn (ImportBatch $batch): bool => $batch->organization() === $organization && (null === $type || $batch->type() === $type) && (null === $status || $batch->status() === $status)));
    }
}
