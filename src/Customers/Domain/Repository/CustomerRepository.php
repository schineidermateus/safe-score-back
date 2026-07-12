<?php

declare(strict_types=1);

namespace App\Customers\Domain\Repository;

use App\Customers\Domain\Entity\Customer;
use App\Customers\Domain\Enum\CustomerStatus;
use App\Organizations\Domain\ValueObject\OrganizationId;

interface CustomerRepository
{
    public function save(Customer $customer): void;

    public function findById(OrganizationId $organizationId, string $customerId): ?Customer;

    public function documentExists(
        OrganizationId $organizationId,
        string $document,
        ?string $exceptCustomerId = null,
    ): bool;

    /**
     * @return list<Customer>
     */
    public function list(
        OrganizationId $organizationId,
        ?string $search,
        ?CustomerStatus $status,
        int $page,
        int $perPage,
        string $sort,
    ): array;

    public function countMatching(
        OrganizationId $organizationId,
        ?string $search,
        ?CustomerStatus $status,
    ): int;
}
