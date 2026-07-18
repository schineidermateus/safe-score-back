<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Service;

use App\Reporting\Domain\Model\PaymentHistoryResult;
use App\Reporting\Domain\Model\ReceivableFinancialAggregate;
use App\Reporting\Domain\ValueObject\DecimalPercentage;

final readonly class PaymentHistoryCalculator
{
    public function calculate(ReceivableFinancialAggregate $aggregate): PaymentHistoryResult
    {
        if (0 === $aggregate->paidReceivablesCount) {
            return PaymentHistoryResult::unavailable();
        }

        $average = bcround(
            bcdiv((string) $aggregate->totalPaymentDelayDays, (string) $aggregate->paidReceivablesCount, 4),
            2,
            \RoundingMode::HalfAwayFromZero,
        );

        return PaymentHistoryResult::available(
            $aggregate->paidReceivablesCount,
            $aggregate->onTimePaidReceivablesCount,
            $aggregate->latePaidReceivablesCount,
            (string) DecimalPercentage::fromCounts($aggregate->onTimePaidReceivablesCount, $aggregate->paidReceivablesCount),
            $average,
            $aggregate->maximumPaymentDelayDays,
        );
    }
}
