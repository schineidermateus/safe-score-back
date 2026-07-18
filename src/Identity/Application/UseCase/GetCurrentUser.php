<?php

declare(strict_types=1);

namespace App\Identity\Application\UseCase;

use App\Identity\Application\DTO\UserOutput;
use App\Organizations\Application\Context\CurrentMembershipProviderInterface;
use App\Organizations\Application\DTO\MembershipOutput;
use App\Organizations\Application\DTO\OrganizationOutput;

final readonly class GetCurrentUser
{
    public function __construct(
        private CurrentMembershipProviderInterface $memberships,
    ) {
    }

    /** @return array{user: array<string, int|string>, organization: array<string, int|string|null>, membership: array<string, int|string>} */
    public function execute(): array
    {
        $membership = $this->memberships->currentMembership();

        return [
            'user' => UserOutput::fromEntity($membership->user())->toArray(),
            'organization' => OrganizationOutput::fromEntity($membership->organization())->toArray(),
            'membership' => MembershipOutput::fromEntity($membership)->toArray(),
        ];
    }
}
