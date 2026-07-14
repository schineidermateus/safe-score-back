<?php

declare(strict_types=1);

namespace App\Organizations\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Organizations\Application\DTO\MembershipOutput;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Organizations\Domain\Repository\OrganizationMembershipRepository;
use App\Shared\Domain\Exception\DomainException;

final readonly class ChangeMembershipRole
{
    public function __construct(
        private OrganizationMembershipRepository $memberships,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private AuthorizationService $authorization,
    ) {
    }

    public function execute(int $membershipId, MembershipRole $role): MembershipOutput
    {
        $organization = $this->currentOrganization->currentOrganization();
        $membership = $this->memberships->findByIdAndOrganization($membershipId, $organization)
            ?? throw new DomainException('MEMBERSHIP_NOT_FOUND', 'Vínculo não encontrado.', 404);
        $this->authorization->assertCanManageMembership($membership, $role);

        if (
            MembershipRole::Owner === $membership->role()
            && MembershipRole::Owner !== $role
            && $this->memberships->countActiveOwners($organization) <= 1
        ) {
            throw new DomainException('LAST_OWNER_REQUIRED', 'A organização deve manter ao menos um OWNER ativo.', 409);
        }

        $membership->changeRole($role, new \DateTimeImmutable());
        $this->memberships->save($membership);

        return MembershipOutput::fromEntity($membership);
    }
}
