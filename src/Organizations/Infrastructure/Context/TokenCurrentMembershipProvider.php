<?php

declare(strict_types=1);

namespace App\Organizations\Infrastructure\Context;

use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Organizations\Application\Context\CurrentMembershipProviderInterface;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Repository\OrganizationMembershipRepository;
use App\Shared\Domain\Exception\DomainException;

/**
 * Provider de vínculo atual para produção: garante o multi-tenant exigindo um
 * vínculo ativo entre o usuário autenticado e a organização do token.
 */
final readonly class TokenCurrentMembershipProvider implements CurrentMembershipProviderInterface
{
    public function __construct(
        private OrganizationMembershipRepository $memberships,
        private CurrentUserProviderInterface $users,
        private CurrentOrganizationProviderInterface $organizations,
    ) {
    }

    public function currentMembership(): OrganizationMembership
    {
        $membership = $this->memberships->findByOrganizationAndUser(
            $this->organizations->currentOrganization(),
            $this->users->currentUser(),
        );

        if (null === $membership || !$membership->grantsAccess()) {
            throw new DomainException('MEMBERSHIP_REQUIRED', 'Vínculo ativo com a organização é obrigatório.', 403);
        }

        return $membership;
    }
}
