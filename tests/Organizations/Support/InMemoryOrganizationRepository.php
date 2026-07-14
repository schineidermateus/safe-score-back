<?php

declare(strict_types=1);

namespace App\Tests\Organizations\Support;

use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Repository\OrganizationRepository;
use App\Tests\Support\EntityId;

final class InMemoryOrganizationRepository implements OrganizationRepository
{
    /** @var array<int, Organization> */
    private array $organizations = [];

    private int $nextId = 1;

    public function save(Organization $organization): void
    {
        if (null === $organization->id()) {
            EntityId::assign($organization, $this->nextId++);
        }

        $this->organizations[$organization->requireId()] = $organization;
    }

    public function findById(int $id): ?Organization
    {
        return $this->organizations[$id] ?? null;
    }

    public function findByDocument(string $document): ?Organization
    {
        foreach ($this->organizations as $organization) {
            if ($organization->document() === $document) {
                return $organization;
            }
        }

        return null;
    }
}
