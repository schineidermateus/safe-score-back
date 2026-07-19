<?php

declare(strict_types=1);

namespace App\Organizations\Domain\Repository;

use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;

interface OrganizationMembershipRepository
{
    public function save(OrganizationMembership $membership): void;

    public function findByIdAndOrganization(int $id, Organization $organization): ?OrganizationMembership;

    public function findByOrganizationAndUser(Organization $organization, User $user): ?OrganizationMembership;

    public function findActiveByUserAndOrganizationId(User $user, int $organizationId): ?OrganizationMembership;

    /** @return list<OrganizationMembership> */
    public function listAccessibleByUser(User $user): array;

    /** @return list<OrganizationMembership> */
    public function listByOrganization(Organization $organization): array;

    public function countActiveOwners(Organization $organization): int;

    public function lockActiveOwners(Organization $organization): void;
}
