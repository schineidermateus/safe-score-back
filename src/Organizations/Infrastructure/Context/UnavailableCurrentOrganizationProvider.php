<?php

declare(strict_types=1);

namespace App\Organizations\Infrastructure\Context;

use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Organizations\Domain\Entity\Organization;
use App\Shared\Domain\Exception\DomainException;

final class UnavailableCurrentOrganizationProvider implements CurrentOrganizationProviderInterface
{
    public function currentOrganization(): Organization
    {
        throw new DomainException('ORGANIZATION_CONTEXT_REQUIRED', 'Nenhuma organização ativa está disponível.', 403);
    }
}
