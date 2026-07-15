<?php

declare(strict_types=1);

namespace App\Customers\Domain\Repository;

use App\Customers\Domain\Entity\Customer;
use App\Customers\Domain\Enum\CustomerStatus;
use App\Organizations\Domain\Entity\Organization;

interface CustomerRepository
{
    public function save(Organization $organization, Customer $customer): void;

    public function findById(Organization $organization, int $customerId): ?Customer;

    public function documentExists(
        Organization $organization,
        string $document,
        ?int $exceptCustomerId = null,
    ): bool;

    /**
     * @return list<Customer>
     */
    public function list(
        Organization $organization,
        ?string $search,
        ?CustomerStatus $status,
        int $page,
        int $perPage,
        string $sort,
    ): array;

    public function countMatching(
        Organization $organization,
        ?string $search,
        ?CustomerStatus $status,
    ): int;
}
