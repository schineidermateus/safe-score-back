<?php

declare(strict_types=1);

namespace App\Organizations\Infrastructure\Context;

use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Organizations\Application\Context\CurrentMembershipProviderInterface;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Repository\OrganizationMembershipRepository;
use App\Shared\Domain\Exception\DomainException;

final readonly class DevelopmentCurrentMembershipProvider implements CurrentMembershipProviderInterface
{
    public function __construct(
        private OrganizationMembershipRepository $memberships,
        private CurrentUserProviderInterface $users,
        private CurrentOrganizationProviderInterface $organizations,
        string $environment,
    ) {
        if (!in_array($environment, ['dev', 'test'], true)) {
            throw new \LogicException('Development membership provider cannot run outside dev or test.');
        }
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
