<?php

declare(strict_types=1);

namespace App\Organizations\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Identity\Domain\Repository\UserRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Organizations\Application\DTO\AddMemberInput;
use App\Organizations\Application\DTO\MembershipOutput;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Organizations\Domain\Repository\OrganizationMembershipRepository;
use App\Shared\Domain\Exception\DomainException;

final readonly class AddUserToOrganization
{
    public function __construct(
        private UserRepository $users,
        private OrganizationMembershipRepository $memberships,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private AuthorizationService $authorization,
    ) {
    }

    public function execute(AddMemberInput $input): MembershipOutput
    {
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

        $membership = OrganizationMembership::join($organization, $user, $role, new \DateTimeImmutable());
        $this->memberships->save($membership);

        return MembershipOutput::fromEntity($membership);
    }
}
