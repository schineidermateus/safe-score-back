<?php

declare(strict_types=1);

namespace App\Identity\Application\UseCase;

use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Identity\Application\DTO\UserOutput;
use App\Organizations\Application\Context\CurrentMembershipProviderInterface;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Organizations\Application\DTO\MembershipOutput;
use App\Organizations\Application\DTO\OrganizationOutput;

final readonly class GetCurrentUser
{
    public function __construct(
        private CurrentUserProviderInterface $users,
        private CurrentOrganizationProviderInterface $organizations,
        private CurrentMembershipProviderInterface $memberships,
    ) {
    }

    /** @return array{user: array<string, int|string>, organization: array<string, int|string|null>, membership: array<string, int|string>} */
    public function execute(): array
    {
        return [
            'user' => UserOutput::fromEntity($this->users->currentUser())->toArray(),
            'organization' => OrganizationOutput::fromEntity($this->organizations->currentOrganization())->toArray(),
            'membership' => MembershipOutput::fromEntity($this->memberships->currentMembership())->toArray(),
        ];
    }
}
