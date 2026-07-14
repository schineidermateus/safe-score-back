<?php

declare(strict_types=1);

namespace App\Organizations\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Organizations\Application\DTO\MembershipOutput;
use App\Organizations\Domain\Repository\OrganizationMembershipRepository;

final readonly class ListOrganizationMembers
{
    public function __construct(
        private OrganizationMembershipRepository $memberships,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private AuthorizationService $authorization,
    ) {
    }

    /** @return list<MembershipOutput> */
    public function execute(): array
    {
        $this->authorization->assertGranted(AuthorizationAction::ManageMembers);

        return array_map(
            MembershipOutput::fromEntity(...),
            $this->memberships->listByOrganization($this->currentOrganization->currentOrganization()),
        );
    }
}
