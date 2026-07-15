<?php

declare(strict_types=1);

namespace App\Tests\Organizations\Support;

use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Organizations\Domain\Repository\OrganizationMembershipRepository;
use App\Tests\Support\EntityId;

final class InMemoryMembershipRepository implements OrganizationMembershipRepository
{
    /** @var array<int, OrganizationMembership> */
    private array $memberships = [];
    private int $nextId = 1;

    public function save(OrganizationMembership $membership): void
    {
        if (null === $membership->id()) {
            EntityId::assign($membership, $this->nextId++);
        }
        $this->memberships[$membership->requireId()] = $membership;
    }

    public function findByIdAndOrganization(int $id, Organization $organization): ?OrganizationMembership
    {
        $membership = $this->memberships[$id] ?? null;

        return null !== $membership && $membership->organization() === $organization ? $membership : null;
    }

    public function findByOrganizationAndUser(Organization $organization, User $user): ?OrganizationMembership
    {
        foreach ($this->memberships as $membership) {
            if ($membership->organization() === $organization && $membership->user() === $user) {
                return $membership;
            }
        }

        return null;
    }

    public function listByOrganization(Organization $organization): array
    {
        return array_values(array_filter(
            $this->memberships,
            static fn (OrganizationMembership $membership): bool => $membership->organization() === $organization,
        ));
    }

    public function countActiveOwners(Organization $organization): int
    {
        return count(array_filter(
            $this->memberships,
            static fn (OrganizationMembership $membership): bool => $membership->organization() === $organization
                && MembershipRole::Owner === $membership->role()
                && $membership->grantsAccess(),
        ));
    }

    public function lockActiveOwners(Organization $organization): void
    {
    }
}
