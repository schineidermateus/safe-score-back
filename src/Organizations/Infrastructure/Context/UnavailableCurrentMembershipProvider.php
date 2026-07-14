<?php

declare(strict_types=1);

namespace App\Organizations\Infrastructure\Context;

use App\Organizations\Application\Context\CurrentMembershipProviderInterface;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Shared\Domain\Exception\DomainException;

final class UnavailableCurrentMembershipProvider implements CurrentMembershipProviderInterface
{
    public function currentMembership(): OrganizationMembership
    {
        throw new DomainException('MEMBERSHIP_REQUIRED', 'Vínculo ativo com a organização é obrigatório.', 403);
    }
}
