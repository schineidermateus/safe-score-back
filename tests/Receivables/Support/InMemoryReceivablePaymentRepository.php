<?php

declare(strict_types=1);

namespace App\Tests\Receivables\Support;

use App\Organizations\Domain\Entity\Organization;
use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\Entity\ReceivablePayment;
use App\Receivables\Domain\Repository\ReceivablePaymentRepository;
use App\Shared\Domain\Exception\DomainException;
use App\Tests\Support\EntityId;

final class InMemoryReceivablePaymentRepository implements ReceivablePaymentRepository
{
    /** @var array<int, ReceivablePayment> */
    private array $items = [];

    public function save(Organization $organization, ReceivablePayment $payment): void
    {
        if ($payment->organization() !== $organization || $payment->receivable()->organization() !== $organization) {
            throw new DomainException('RECEIVABLE_TENANT_MISMATCH', 'Tenant mismatch.', 403);
        }
        if (null === $payment->id()) {
            EntityId::assign($payment, count($this->items) + 1);
        }
        $this->items[$payment->requireId()] = $payment;
    }

    public function listByReceivableAndOrganization(Receivable $receivable, Organization $organization): array
    {
        if ($receivable->organization() !== $organization) {
            throw new DomainException('RECEIVABLE_NOT_FOUND', 'Receivable not found.', 404);
        }

        return array_values(array_filter($this->items, static fn (ReceivablePayment $payment): bool => $payment->organization() === $organization && $payment->receivable() === $receivable));
    }

    /** @return list<ReceivablePayment> */
    public function all(): array
    {
        return array_values($this->items);
    }
}
