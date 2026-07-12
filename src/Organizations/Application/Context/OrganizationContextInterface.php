<?php

declare(strict_types=1);

namespace App\Organizations\Application\Context;

use App\Organizations\Domain\ValueObject\OrganizationId;

interface OrganizationContextInterface
{
    public function organizationId(): ?OrganizationId;

    public function requireOrganizationId(): OrganizationId;
}
