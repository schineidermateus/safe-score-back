<?php

declare(strict_types=1);

namespace App\Organizations\Infrastructure\Context;

use App\Identity\Application\Context\AuthenticatedTokenProviderInterface;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Repository\OrganizationMembershipRepository;
use App\Organizations\Domain\Repository\OrganizationRepository;
use App\Shared\Domain\Exception\DomainException;

/**
 * Provider de organização atual para produção: resolve o tenant a partir do
 * claim "organization_id" carregado pelo JWT autenticado.
 */
final readonly class TokenCurrentOrganizationProvider implements CurrentOrganizationProviderInterface
{
    public function __construct(
        private OrganizationRepository $organizations,
        private AuthenticatedTokenProviderInterface $tokenProvider,
        private CurrentUserProviderInterface $currentUser,
        private OrganizationMembershipRepository $memberships,
    ) {
    }

    public function currentOrganization(): Organization
    {
        $organizationId = $this->tokenProvider->current()->organizationId;
        $user = $this->currentUser->currentUser();

        if (null === $organizationId) {
            $memberships = $this->memberships->listAccessibleByUser($user);
            if ([] === $memberships) {
                throw new DomainException('MEMBERSHIP_REQUIRED', 'Vínculo ativo com uma organização é obrigatório.', 403);
            }
            if (1 !== count($memberships)) {
                throw new DomainException('ORGANIZATION_SELECTION_REQUIRED', 'Selecione uma organização acessível.', 409);
            }

            return $memberships[0]->organization();
        }

        $membership = $this->memberships->findActiveByUserAndOrganizationId($user, $organizationId)
            ?? throw new DomainException('CURRENT_ORGANIZATION_NOT_FOUND', 'Organização do token não está disponível.', 403);
        $organization = $this->organizations->findById($membership->organization()->requireId())
            ?? throw new DomainException('CURRENT_ORGANIZATION_NOT_FOUND', 'Organização do token não encontrada.', 403);

        if (!$organization->isActive()) {
            throw new DomainException('ORGANIZATION_INACTIVE', 'A organização atual não está ativa.', 403);
        }

        return $organization;
    }
}
