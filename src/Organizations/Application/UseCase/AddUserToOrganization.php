<?php

declare(strict_types=1);

namespace App\Organizations\Application\UseCase;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Identity\Domain\Repository\UserRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Organizations\Application\DTO\AddMemberInput;
use App\Organizations\Application\DTO\MembershipOutput;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Organizations\Domain\Repository\OrganizationMembershipRepository;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class AddUserToOrganization
{
    public function __construct(
        private UserRepository $users,
        private OrganizationMembershipRepository $memberships,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private CurrentUserProviderInterface $currentUser,
        private AuthorizationService $authorization,
        private AuditLogger $audit,
        private TransactionManagerInterface $transactions,
    ) {
    }

    public function execute(AddMemberInput $input): MembershipOutput
    {
        return $this->transactions->transactional(function () use ($input): MembershipOutput {
            $role = MembershipRole::from($input->role);
            $this->authorization->assertGranted(
                MembershipRole::Owner === $role ? AuthorizationAction::AssignOwner : AuthorizationAction::ManageMembers,
            );
            $organization = $this->currentOrganization->currentOrganization();
            $user = $this->users->findById($input->userId)
                ?? throw new DomainException('USER_NOT_FOUND', 'Usuário não encontrado.', 404);

            if (null !== $this->memberships->findByOrganizationAndUser($organization, $user)) {
                throw new DomainException('MEMBERSHIP_ALREADY_EXISTS', 'O usuário já possui vínculo com esta organização.', 409);
            }

            $now = new \DateTimeImmutable();
            $membership = OrganizationMembership::join($organization, $user, $role, $now);
            $this->memberships->save($membership);
            $this->audit->record($organization, $this->currentUser->currentUser(), 'MEMBERSHIP_CREATED', 'OrganizationMembership', $membership->requireId(), null, ['user_id' => $user->requireId(), 'role' => $role->value, 'status' => $membership->status()->value], null, $now);

            return MembershipOutput::fromEntity($membership);
        });
    }
}
