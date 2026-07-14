<?php

declare(strict_types=1);

namespace App\Organizations\Infrastructure\Context;

use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Repository\OrganizationRepository;
use App\Shared\Domain\Exception\DomainException;

final readonly class DevelopmentCurrentOrganizationProvider implements CurrentOrganizationProviderInterface
{
    public function __construct(
        private OrganizationRepository $organizations,
        private int $organizationId,
        string $environment,
    ) {
        if (!in_array($environment, ['dev', 'test'], true)) {
            throw new \LogicException('Development organization provider cannot run outside dev or test.');
        }
    }

    public function currentOrganization(): Organization
    {
        return $this->organizations->findById($this->organizationId)
            ?? throw new DomainException('CURRENT_ORGANIZATION_NOT_FOUND', 'Organização de desenvolvimento não encontrada.', 403);
    }
}
