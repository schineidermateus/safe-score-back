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

final readonly class SuspendMembership
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

    public function execute(int $membershipId): MembershipOutput
    {
        return $this->transactions->transactional(function () use ($membershipId): MembershipOutput {
            $organization = $this->currentOrganization->currentOrganization();
            $this->memberships->lockActiveOwners($organization);
            $membership = $this->memberships->findByIdAndOrganization($membershipId, $organization)
                ?? throw new DomainException('MEMBERSHIP_NOT_FOUND', 'Vínculo não encontrado.', 404);
            $this->authorization->assertCanManageMembership($membership);

            if (MembershipRole::Owner === $membership->role() && $this->memberships->countActiveOwners($organization) <= 1) {
                throw new DomainException('LAST_OWNER_REQUIRED', 'A organização deve manter ao menos um OWNER ativo.', 409);
            }

            $now = new \DateTimeImmutable();
            $previousStatus = $membership->status();
            $membership->suspend($now);
            $this->memberships->save($membership);
            $this->audit->record($organization, $this->currentUser->currentUser(), 'MEMBERSHIP_SUSPENDED', 'OrganizationMembership', $membership->requireId(), ['status' => $previousStatus->value], ['status' => $membership->status()->value], null, $now);

            return MembershipOutput::fromEntity($membership);
        });
    }
}
