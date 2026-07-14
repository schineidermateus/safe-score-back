<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Identity\Domain\Entity\User;
use App\Organizations\Application\Context\CurrentMembershipProviderInterface;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;

final readonly class CurrentContextStub implements CurrentUserProviderInterface, CurrentOrganizationProviderInterface, CurrentMembershipProviderInterface
{
    public function __construct(
        private User $user,
        private Organization $organization,
        private OrganizationMembership $membership,
    ) {
    }

    public function currentUser(): User
    {
        return $this->user;
    }

    public function currentOrganization(): Organization
    {
        return $this->organization;
    }

    public function currentMembership(): OrganizationMembership
    {
        return $this->membership;
    }
}
