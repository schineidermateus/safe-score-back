<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Model;

use App\Reporting\Domain\ValueObject\DecimalAmount;

final readonly class ReceivableFinancialAggregate
{
    public function __construct(
        public int $customerId,
        public DecimalAmount $exposure,
        public DecimalAmount $overdueExposure,
        public int $maximumOverdueDays,
        public int $receivablesCount,
        public int $paidReceivablesCount,
        public int $onTimePaidReceivablesCount,
        public int $latePaidReceivablesCount,
        public int $totalPaymentDelayDays,
        public int $maximumPaymentDelayDays,
        public ?\DateTimeImmutable $lastReceivableUpdate,
    ) {
    }

    public static function empty(int $customerId): self
    {
        return new self($customerId, DecimalAmount::zero(), DecimalAmount::zero(), 0, 0, 0, 0, 0, 0, 0, null);
    }
}
