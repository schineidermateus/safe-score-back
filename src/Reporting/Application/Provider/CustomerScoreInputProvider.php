<?php

declare(strict_types=1);

namespace App\Reporting\Application\Provider;

use App\Organizations\Domain\Entity\Organization;
use App\Reporting\Application\DTO\CustomerScoreInput;
use App\Reporting\Domain\ValueObject\ReferenceDate;

final readonly class CustomerScoreInputProvider
{
    public function __construct(private CustomerFinancialIndicatorsProvider $indicators)
    {
    }

    public function getForCustomer(Organization $organization, int $customerId, ReferenceDate $referenceDate): CustomerScoreInput
    {
        return CustomerScoreInput::fromIndicators($this->indicators->getForCustomer($organization, $customerId, $referenceDate));
    }

    /** @return array<int, CustomerScoreInput> */
    public function getForOrganization(Organization $organization, ReferenceDate $referenceDate): array
    {
        return array_map(CustomerScoreInput::fromIndicators(...), $this->indicators->getForOrganization($organization, $referenceDate));
    }
}
