<?php

declare(strict_types=1);

namespace App\Organizations\Application\UseCase;

use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Organizations\Application\DTO\AccessibleOrganizationOutput;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Repository\OrganizationMembershipRepository;

final readonly class ListAccessibleOrganizations
{
    public function __construct(
        private OrganizationMembershipRepository $memberships,
        private CurrentUserProviderInterface $currentUser,
    ) {
    }

    /** @return list<array<string, int|string|null>> */
    public function execute(): array
    {
        return array_map(
            static fn (OrganizationMembership $membership): array => AccessibleOrganizationOutput::fromMembership($membership)->toArray(),
            $this->memberships->listAccessibleByUser($this->currentUser->currentUser()),
        );
    }
}
