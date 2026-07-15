<?php

declare(strict_types=1);

namespace App\Credit\Domain\Service;

use App\Credit\Domain\Entity\CreditLimit;
use App\Customers\Domain\Entity\Customer;
use App\Organizations\Domain\Entity\Organization;

interface ActiveCreditLimitResolverInterface
{
    public function resolve(
        Organization $organization,
        Customer $customer,
        \DateTimeImmutable $referenceDate,
    ): ?CreditLimit;
}
