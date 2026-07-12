<?php

declare(strict_types=1);

namespace App\Organizations\Application\Context;

use App\Organizations\Domain\ValueObject\OrganizationId;
use App\Shared\Domain\Exception\DomainException;

final class OrganizationContext implements OrganizationContextInterface
{
    private ?OrganizationId $organizationId = null;

    public function set(?OrganizationId $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function clear(): void
    {
        $this->organizationId = null;
    }

    public function organizationId(): ?OrganizationId
    {
        return $this->organizationId;
    }

    public function requireOrganizationId(): OrganizationId
    {
        return $this->organizationId ?? throw new DomainException('ORGANIZATION_CONTEXT_REQUIRED', 'Nenhuma organização ativa está disponível.', 403);
    }
}
