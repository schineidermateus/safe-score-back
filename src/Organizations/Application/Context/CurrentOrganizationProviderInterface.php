<?php

declare(strict_types=1);

namespace App\Organizations\Application\Context;

use App\Organizations\Domain\Entity\Organization;

interface CurrentOrganizationProviderInterface
{
    public function currentOrganization(): Organization;
}
