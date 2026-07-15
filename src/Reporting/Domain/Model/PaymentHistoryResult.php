<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Model;

use App\Reporting\Domain\Enum\FinancialIndicatorStatus;

final readonly class PaymentHistoryResult
{
    private function __construct(
        public FinancialIndicatorStatus $status,
        public int $paidReceivablesCount,
        public int $onTimePaidReceivablesCount,
        public int $latePaidReceivablesCount,
        public ?string $onTimePaymentPercentage,
        public ?string $averagePaymentDelayDays,
        public ?int $maximumPaymentDelayDays,
    ) {
    }

    public static function unavailable(): self
    {
        return new self(FinancialIndicatorStatus::InsufficientHistory, 0, 0, 0, null, null, null);
    }

    public static function available(
        int $paidReceivablesCount,
        int $onTimePaidReceivablesCount,
        int $latePaidReceivablesCount,
        string $onTimePaymentPercentage,
        string $averagePaymentDelayDays,
        int $maximumPaymentDelayDays,
    ): self {
        if ($paidReceivablesCount <= 0 || $onTimePaidReceivablesCount + $latePaidReceivablesCount !== $paidReceivablesCount) {
            throw new \InvalidArgumentException('Payment history counts are inconsistent.');
        }

        return new self(
            FinancialIndicatorStatus::Available,
            $paidReceivablesCount,
            $onTimePaidReceivablesCount,
            $latePaidReceivablesCount,
            $onTimePaymentPercentage,
            $averagePaymentDelayDays,
            $maximumPaymentDelayDays,
        );
    }
}
