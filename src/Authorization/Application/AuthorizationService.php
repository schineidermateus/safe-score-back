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

        if (!$membership->grantsAccess() || !in_array($action, $this->actionsFor($membership->role()), true)) {
            throw new DomainException('ACCESS_DENIED', 'Acesso negado.', 403);
        }
    }

    public function assertCanManageMembership(
        OrganizationMembership $target,
        ?MembershipRole $newRole = null,
    ): void {
        $current = $this->memberships->currentMembership();
        $this->assertGranted(AuthorizationAction::ManageMembers);

        if (
            MembershipRole::Admin === $current->role()
            && (MembershipRole::Owner === $target->role() || MembershipRole::Owner === $newRole)
        ) {
            throw new DomainException('OWNER_MANAGEMENT_FORBIDDEN', 'Somente OWNER pode gerenciar outro OWNER.', 403);
        }
    }

    /** @return list<AuthorizationAction> */
    private function actionsFor(MembershipRole $role): array
    {
        $operational = [
            AuthorizationAction::ViewData,
            AuthorizationAction::ManageCustomers,
            AuthorizationAction::ManageCredit,
            AuthorizationAction::ManageReceivables,
            AuthorizationAction::ImportData,
            AuthorizationAction::ResolveAlerts,
        ];

        return match ($role) {
            MembershipRole::Owner => [...AuthorizationAction::cases()],
            MembershipRole::Admin => [...$operational, AuthorizationAction::ManageMembers],
            MembershipRole::Analyst => $operational,
            MembershipRole::Viewer => [AuthorizationAction::ViewData],
        };
    }
}
