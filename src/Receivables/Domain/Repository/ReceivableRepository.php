<?php

declare(strict_types=1);

namespace App\Receivables\Domain\Repository;

use App\Organizations\Domain\Entity\Organization;
use App\Receivables\Domain\Entity\Receivable;

interface ReceivableRepository
{
    public function save(Organization $organization, Receivable $receivable): void;

    public function findByIdAndOrganization(int $id, Organization $organization): ?Receivable;

    public function findByIdAndOrganizationForUpdate(int $id, Organization $organization): ?Receivable;

    public function existsByExternalKey(Organization $organization, string $source, string $externalId, ?int $exceptId = null): bool;

    public function findByExternalKey(Organization $organization, string $source, string $externalId, bool $forUpdate = false): ?Receivable;

    /** @return list<Receivable> */
    public function list(Organization $organization, ReceivableCriteria $criteria): array;

    public function countMatching(Organization $organization, ReceivableCriteria $criteria): int;
}
