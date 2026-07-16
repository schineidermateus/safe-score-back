<?php

declare(strict_types=1);

namespace App\Organizations\Infrastructure\Context;

use App\Identity\Application\Context\AuthenticatedTokenProviderInterface;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Organizations\Domain\Entity\Organization;
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
    ) {
    }

    public function currentOrganization(): Organization
    {
        $organizationId = $this->tokenProvider->current()->requireOrganizationId();

        return $this->organizations->findById($organizationId)
            ?? throw new DomainException('CURRENT_ORGANIZATION_NOT_FOUND', 'Organização do token não encontrada.', 403);
    }
}
