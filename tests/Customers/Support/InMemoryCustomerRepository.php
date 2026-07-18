<?php

declare(strict_types=1);

namespace App\Tests\Customers\Support;

use App\Customers\Domain\Entity\Customer;
use App\Customers\Domain\Enum\CustomerStatus;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Domain\Entity\Organization;
use App\Tests\Support\EntityId;

final class InMemoryCustomerRepository implements CustomerRepository
{
    /** @var array<int, Customer> */
    private array $customers = [];
    private int $nextId = 1;

    public function save(Organization $organization, Customer $customer): void
    {
        if ($customer->organization() !== $organization) {
            throw new \LogicException('Customer tenant mismatch.');
        }

        if (null === $customer->id()) {
            EntityId::assign($customer, $this->nextId++);
        }

        $this->customers[$customer->requireId()] = $customer;
    }

    public function findById(Organization $organization, int $customerId): ?Customer
    {
        $customer = $this->customers[$customerId] ?? null;
        if (null === $customer || $customer->organization() !== $organization || null !== $customer->deletedAt()) {
            return null;
        }

        return $customer;
    }

    public function findByDocument(Organization $organization, string $document): ?Customer
    {
        foreach ($this->customers as $customer) {
            if ($customer->organization() === $organization && $customer->document() === $document && null === $customer->deletedAt()) {
                return $customer;
            }
        }

        return null;
    }

    public function findByExternalId(Organization $organization, string $externalId): ?Customer
    {
        foreach ($this->customers as $customer) {
            if ($customer->organization() === $organization && $customer->externalId() === $externalId && null === $customer->deletedAt()) {
                return $customer;
            }
        }

        return null;
    }

    public function documentExists(Organization $organization, string $document, ?int $exceptCustomerId = null): bool
    {
        foreach ($this->customers as $customer) {
            if (
                $customer->organization() === $organization
                && $customer->document() === $document
                && $customer->id() !== $exceptCustomerId
            ) {
                return true;
            }
        }

        return false;
    }

    public function externalIdExists(Organization $organization, string $externalId, ?int $exceptCustomerId = null): bool
    {
        foreach ($this->customers as $customer) {
            if ($customer->organization() === $organization && $customer->externalId() === $externalId && $customer->id() !== $exceptCustomerId) {
                return true;
            }
        }

        return false;
    }

    public function list(
        Organization $organization,
        ?string $search,
        ?CustomerStatus $status,
        int $page,
        int $perPage,
        string $sort,
    ): array {
        $customers = $this->filtered($organization, $search, $status);
        usort($customers, static fn (Customer $a, Customer $b): int => $a->legalName() <=> $b->legalName());
        if (str_starts_with($sort, '-')) {
            $customers = array_reverse($customers);
        }

        return array_values(array_slice($customers, ($page - 1) * $perPage, $perPage));
    }

    public function countMatching(Organization $organization, ?string $search, ?CustomerStatus $status): int
    {
        return count($this->filtered($organization, $search, $status));
    }

    public function listAll(Organization $organization): array
    {
        return $this->filtered($organization, null, null);
    }

    /** @return list<Customer> */
    private function filtered(Organization $organization, ?string $search, ?CustomerStatus $status): array
    {
        return array_values(array_filter(
            $this->customers,
            static function (Customer $customer) use ($organization, $search, $status): bool {
                if (
                    $customer->organization() !== $organization
                    || null !== $customer->deletedAt()
                    || (null !== $status && $customer->status() !== $status)
                ) {
                    return false;
                }
                if (null === $search || '' === trim($search)) {
                    return true;
                }

                return str_contains(mb_strtolower($customer->legalName()), mb_strtolower(trim($search)));
            },
        ));
    }
}
