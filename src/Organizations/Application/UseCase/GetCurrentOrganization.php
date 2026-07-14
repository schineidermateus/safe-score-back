<?php

declare(strict_types=1);

namespace App\Organizations\Application\UseCase;

use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Organizations\Application\DTO\OrganizationOutput;

final readonly class GetCurrentOrganization
{
    public function __construct(private CurrentOrganizationProviderInterface $organizations)
    {
    }

    public function execute(): OrganizationOutput
    {
        return OrganizationOutput::fromEntity($this->organizations->currentOrganization());
    }
}
