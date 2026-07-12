<?php

declare(strict_types=1);

namespace App\Tests\Customers\Support;

use App\Customers\Domain\Entity\Customer;
use App\Customers\Domain\Enum\CustomerStatus;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Domain\ValueObject\OrganizationId;

final class InMemoryCustomerRepository implements CustomerRepository
{
    /**
     * @var array<string, Customer>
     */
    private array $customers = [];

    public function save(Customer $customer): void
    {
        $this->customers[$customer->id()] = $customer;
    }

    public function findById(OrganizationId $organizationId, string $customerId): ?Customer
    {
        $customer = $this->customers[$customerId] ?? null;

        if (
            null === $customer
            || !$customer->organizationId()->equals($organizationId)
            || null !== $customer->deletedAt()
        ) {
            return null;
        }

        return $customer;
    }

    public function documentExists(
        OrganizationId $organizationId,
        string $document,
        ?string $exceptCustomerId = null,
    ): bool {
        foreach ($this->customers as $customer) {
            if (
                $customer->organizationId()->equals($organizationId)
                && $customer->document() === $document
                && $customer->id() !== $exceptCustomerId
            ) {
                return true;
            }
        }

        return false;
    }

    public function list(
        OrganizationId $organizationId,
        ?string $search,
        ?CustomerStatus $status,
        int $page,
        int $perPage,
        string $sort,
    ): array {
        $customers = $this->filtered($organizationId, $search, $status);
        usort(
            $customers,
            static fn (Customer $left, Customer $right): int => $left->legalName() <=> $right->legalName(),
        );

        if (str_starts_with($sort, '-')) {
            $customers = array_reverse($customers);
        }

        return array_values(array_slice($customers, ($page - 1) * $perPage, $perPage));
    }

    public function countMatching(
        OrganizationId $organizationId,
        ?string $search,
        ?CustomerStatus $status,
    ): int {
        return count($this->filtered($organizationId, $search, $status));
    }

    /**
     * @return list<Customer>
     */
    private function filtered(
        OrganizationId $organizationId,
        ?string $search,
        ?CustomerStatus $status,
    ): array {
        return array_values(array_filter(
            $this->customers,
            static function (Customer $customer) use ($organizationId, $search, $status): bool {
                if (
                    !$customer->organizationId()->equals($organizationId)
                    || null !== $customer->deletedAt()
                    || (null !== $status && $customer->status() !== $status)
                ) {
                    return false;
                }

                if (null === $search || '' === trim($search)) {
                    return true;
                }

                $haystack = implode(' ', array_filter([
                    $customer->legalName(),
                    $customer->tradeName(),
                    $customer->document(),
                    $customer->externalId(),
                ]));

                return str_contains(mb_strtolower($haystack), mb_strtolower(trim($search)));
            },
        ));
    }
}
