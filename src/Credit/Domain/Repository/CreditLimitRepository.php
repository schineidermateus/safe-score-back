<?php

declare(strict_types=1);

namespace App\Credit\Domain\Repository;

use App\Credit\Domain\Entity\CreditLimit;
use App\Customers\Domain\Entity\Customer;
use App\Organizations\Domain\Entity\Organization;

interface CreditLimitRepository
{
    public function save(Organization $organization, CreditLimit $creditLimit): void;

    public function findByIdAndOrganization(int $id, Organization $organization): ?CreditLimit;

    /** @return list<CreditLimit> */
    public function findHistoryByCustomerAndOrganization(
        Customer $customer,
        Organization $organization,
        int $page,
        int $perPage,
    ): array;

    public function countHistoryByCustomerAndOrganization(Customer $customer, Organization $organization): int;

    /** @return list<CreditLimit> */
    public function findActiveByCustomerAndDate(
        Customer $customer,
        Organization $organization,
        \DateTimeImmutable $referenceDate,
    ): array;

    public function existsOverlappingActivePeriod(
        Customer $customer,
        Organization $organization,
        \DateTimeImmutable $validFrom,
        ?\DateTimeImmutable $validUntil,
        ?int $exceptCreditLimitId = null,
    ): bool;

    public function lockCustomer(Customer $customer, Organization $organization): void;

    public function findIdenticalActive(
        Customer $customer,
        Organization $organization,
        string $amount,
        \DateTimeImmutable $validFrom,
        ?\DateTimeImmutable $validUntil,
        string $reason,
    ): ?CreditLimit;
}
