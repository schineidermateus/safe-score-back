<?php

declare(strict_types=1);

namespace App\Organizations\Application\Context;

use App\Organizations\Domain\Entity\OrganizationMembership;

interface CurrentMembershipProviderInterface
{
    public function currentMembership(): OrganizationMembership;
}
