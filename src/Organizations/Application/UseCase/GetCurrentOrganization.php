<?php

declare(strict_types=1);

namespace App\Organizations\Application\UseCase;

use App\Organizations\Application\Context\CurrentMembershipProviderInterface;
use App\Organizations\Application\DTO\OrganizationOutput;

final readonly class GetCurrentOrganization
{
    public function __construct(private CurrentMembershipProviderInterface $memberships)
    {
    }

    public function execute(): OrganizationOutput
    {
        return OrganizationOutput::fromEntity($this->memberships->currentMembership()->organization());
    }
}
