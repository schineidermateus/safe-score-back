<?php

declare(strict_types=1);

namespace App\Authorization\Application;

use App\Authorization\Domain\AuthorizationAction;
use App\Organizations\Application\Context\CurrentMembershipProviderInterface;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Shared\Domain\Exception\DomainException;

final readonly class AuthorizationService
{
    public function __construct(private CurrentMembershipProviderInterface $memberships)
    {
    }

    public function assertGranted(AuthorizationAction $action): void
    {
        $membership = $this->memberships->currentMembership();

        if (!$membership->grantsAccess() || !$membership->hasCapability($action->value)) {
            throw new DomainException('ACCESS_DENIED', 'Acesso negado.', 403);
        }
    }

    public function assertCanManageMembership(
        OrganizationMembership $target,
        ?MembershipRole $newRole = null,
    ): void {
        $requiresOwnerAssignment = MembershipRole::Owner === $target->role() || MembershipRole::Owner === $newRole;
        $this->assertGranted($requiresOwnerAssignment ? AuthorizationAction::AssignOwner : AuthorizationAction::ManageMembers);
    }
}
