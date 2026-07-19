<?php

declare(strict_types=1);

namespace App\Organizations\Application\UseCase;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Organizations\Application\DTO\MembershipOutput;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Organizations\Domain\Repository\OrganizationMembershipRepository;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class ChangeMembershipRole
{
    public function __construct(
        private OrganizationMembershipRepository $memberships,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private AuthorizationService $authorization,
        private TransactionManagerInterface $transactions,
        private CurrentUserProviderInterface $currentUser,
        private AuditLogger $audit,
    ) {
    }

    public function execute(int $membershipId, MembershipRole $role): MembershipOutput
    {
        return $this->transactions->transactional(function () use ($membershipId, $role): MembershipOutput {
            $organization = $this->currentOrganization->currentOrganization();
            $this->memberships->lockActiveOwners($organization);
            $membership = $this->memberships->findByIdAndOrganization($membershipId, $organization)
                ?? throw new DomainException('MEMBERSHIP_NOT_FOUND', 'Vínculo não encontrado.', 404);
            $this->authorization->assertCanManageMembership($membership, $role);
            $previousRole = $membership->role();

            if (MembershipRole::Owner === $previousRole && MembershipRole::Owner !== $role && $this->memberships->countActiveOwners($organization) <= 1) {
                throw new DomainException('LAST_OWNER_REQUIRED', 'A organização deve manter ao menos um OWNER ativo.', 409);
            }

            $now = new \DateTimeImmutable();
            $membership->changeRole($role, $now);
            $this->memberships->save($membership);
            $this->audit->record($organization, $this->currentUser->currentUser(), 'MEMBERSHIP_ROLE_CHANGED', 'OrganizationMembership', $membership->requireId(), ['role' => $previousRole->value], ['role' => $role->value], null, $now);

            return MembershipOutput::fromEntity($membership);
        });
    }
}
