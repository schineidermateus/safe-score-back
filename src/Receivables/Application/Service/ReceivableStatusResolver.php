<?php

declare(strict_types=1);

namespace App\Receivables\Application\Service;

use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\Enum\ReceivableStatus;
use App\Receivables\Domain\Service\ReceivableStatusResolverInterface;

final class ReceivableStatusResolver implements ReceivableStatusResolverInterface
{
    public function resolve(Receivable $receivable, \DateTimeImmutable $referenceDate): ReceivableStatus
    {
        if (ReceivableStatus::Cancelled === $receivable->status()) {
            return ReceivableStatus::Cancelled;
        }
        if ('0.00' === $receivable->openAmount()) {
            return ReceivableStatus::Paid;
        }
        if ($receivable->dueDate() < $referenceDate) {
            return ReceivableStatus::Overdue;
        }

        return '0.00' !== $receivable->paidAmount() ? ReceivableStatus::PartiallyPaid : ReceivableStatus::Open;
    }
}
