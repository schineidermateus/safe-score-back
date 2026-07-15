<?php

declare(strict_types=1);

namespace App\Tests\Credit\Support;

use App\Credit\Domain\Entity\CreditLimit;
use App\Credit\Domain\Enum\CreditLimitStatus;
use App\Credit\Domain\Repository\CreditLimitRepository;
use App\Customers\Domain\Entity\Customer;
use App\Organizations\Domain\Entity\Organization;
use App\Tests\Support\EntityId;

final class InMemoryCreditLimitRepository implements CreditLimitRepository
{
    /** @var array<int, CreditLimit> */
    private array $items = [];

    public function save(Organization $organization, CreditLimit $creditLimit): void
    {
        if ($creditLimit->organization() !== $organization) {
            throw new \LogicException('Tenant mismatch.');
        }
        if (null === $creditLimit->id()) {
            EntityId::assign($creditLimit, count($this->items) + 1);
        }
        $this->items[$creditLimit->requireId()] = $creditLimit;
    }

    public function findByIdAndOrganization(int $id, Organization $organization): ?CreditLimit
    {
        $item = $this->items[$id] ?? null;

        return null !== $item && $item->organization() === $organization ? $item : null;
    }

    public function findHistoryByCustomerAndOrganization(Customer $customer, Organization $organization, int $page, int $perPage): array
    {
        $items = array_values(array_filter(
            $this->items,
            static fn (CreditLimit $limit): bool => $limit->organization() === $organization && $limit->customer() === $customer,
        ));
        usort($items, static fn (CreditLimit $a, CreditLimit $b): int => $b->createdAt() <=> $a->createdAt()
            ?: $b->requireId() <=> $a->requireId());

        return array_slice($items, ($page - 1) * $perPage, $perPage);
    }

    public function countHistoryByCustomerAndOrganization(Customer $customer, Organization $organization): int
    {
        return count($this->findHistoryByCustomerAndOrganization($customer, $organization, 1, \PHP_INT_MAX));
    }

    public function findActiveByCustomerAndDate(Customer $customer, Organization $organization, \DateTimeImmutable $referenceDate): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (CreditLimit $limit): bool => $limit->organization() === $organization
                && $limit->customer() === $customer
                && $limit->isApplicableAt($referenceDate),
        ));
    }

    public function existsOverlappingActivePeriod(
        Customer $customer,
        Organization $organization,
        \DateTimeImmutable $validFrom,
        ?\DateTimeImmutable $validUntil,
        ?int $exceptId = null,
    ): bool {
        foreach ($this->items as $limit) {
            if (
                $limit->organization() !== $organization
                || $limit->customer() !== $customer
                || CreditLimitStatus::Active !== $limit->status()
                || $limit->id() === $exceptId
            ) {
                continue;
            }

            if (
                (null === $limit->validUntil() || $validFrom <= $limit->validUntil())
                && (null === $validUntil || $limit->validFrom() <= $validUntil)
            ) {
                return true;
            }
        }

        return false;
    }

    public function lockCustomer(Customer $customer, Organization $organization): void
    {
        if ($customer->organization() !== $organization) {
            throw new \LogicException('Tenant mismatch.');
        }
    }

    public function findIdenticalActive(Customer $customer, Organization $organization, string $amount, \DateTimeImmutable $validFrom, ?\DateTimeImmutable $validUntil, string $reason): ?CreditLimit
    {
        foreach ($this->items as $limit) {
            if ($limit->organization() === $organization && $limit->customer() === $customer && CreditLimitStatus::Active === $limit->status() && $limit->amount() === $amount && $limit->validFrom() == $validFrom && $limit->validUntil() == $validUntil && $limit->reason() === $reason) {
                return $limit;
            }
        }

        return null;
    }

    public function findActiveByOrganizationAndDate(Organization $organization, \DateTimeImmutable $referenceDate): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (CreditLimit $limit): bool => $limit->organization() === $organization && $limit->isApplicableAt($referenceDate),
        ));
    }

    /** @return list<CreditLimit> */
    public function all(): array
    {
        return array_values($this->items);
    }
}
