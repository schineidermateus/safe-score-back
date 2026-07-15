<?php

declare(strict_types=1);

namespace App\Tests\Imports\Support;

use App\Imports\Domain\Entity\ImportBatch;
use App\Imports\Domain\Entity\ImportRow;
use App\Imports\Domain\Enum\ImportRowStatus;
use App\Imports\Domain\Repository\ImportRowRepository;
use App\Organizations\Domain\Entity\Organization;
use App\Tests\Support\EntityId;

final class InMemoryImportRowRepository implements ImportRowRepository
{
    /** @var array<int, ImportRow> */ private array $items = [];

    public function save(Organization $organization, ImportRow $row): void
    {
        $this->assertTenant($organization, $row->batch());
        if (null === $row->id()) {
            EntityId::assign($row, count($this->items) + 1);
        } $this->items[$row->requireId()] = $row;
    }

    public function deleteByBatch(Organization $organization, ImportBatch $batch): void
    {
        $this->assertTenant($organization, $batch);
        $this->items = array_filter($this->items, static fn (ImportRow $row): bool => $row->batch() !== $batch);
    }

    public function list(Organization $organization, ImportBatch $batch, int $page, int $perPage, ?ImportRowStatus $status = null): array
    {
        return array_slice($this->filtered($organization, $batch, $status), ($page - 1) * $perPage, $perPage);
    }

    public function findValidForProcessing(Organization $organization, ImportBatch $batch): array
    {
        return $this->filtered($organization, $batch, ImportRowStatus::Valid);
    }

    public function countMatching(Organization $organization, ImportBatch $batch, ?ImportRowStatus $status = null): int
    {
        return count($this->filtered($organization, $batch, $status));
    }

    public function listErrors(Organization $organization, ImportBatch $batch, int $page, int $perPage): array
    {
        $items = array_values(array_filter($this->filtered($organization, $batch, null), static fn (ImportRow $row): bool => in_array($row->status(), [ImportRowStatus::Invalid, ImportRowStatus::Failed], true)));

        return array_slice($items, ($page - 1) * $perPage, $perPage);
    }

    public function countErrors(Organization $organization, ImportBatch $batch): int
    {
        return count($this->listErrors($organization, $batch, 1, \PHP_INT_MAX));
    }

    /** @return list<ImportRow> */
    public function all(): array
    {
        return array_values($this->items);
    }

    /** @return list<ImportRow> */
    private function filtered(Organization $organization, ImportBatch $batch, ?ImportRowStatus $status): array
    {
        $this->assertTenant($organization, $batch);
        $items = array_values(array_filter($this->items, static fn (ImportRow $row): bool => $row->batch() === $batch && (null === $status || $row->status() === $status)));
        usort($items, static fn (ImportRow $a, ImportRow $b): int => $a->rowNumber() <=> $b->rowNumber());

        return $items;
    }

    private function assertTenant(Organization $organization, ImportBatch $batch): void
    {
        if ($batch->organization() !== $organization) {
            throw new \LogicException('Tenant mismatch.');
        }
    }
}
