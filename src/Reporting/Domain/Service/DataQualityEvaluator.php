<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Service;

use App\Reporting\Domain\Enum\FinancialIndicatorStatus;
use App\Reporting\Domain\Model\DataQualityResult;
use App\Reporting\Domain\Model\ReceivableFinancialAggregate;
use App\Reporting\Domain\ValueObject\ReferenceDate;

final readonly class DataQualityEvaluator
{
    public function evaluate(
        bool $hasDocument,
        FinancialIndicatorStatus $creditLimitStatus,
        ReceivableFinancialAggregate $aggregate,
        ?\DateTimeImmutable $lastDataUpdate,
        ReferenceDate $referenceDate,
    ): DataQualityResult {
        $score = 0;
        $reasons = [];

        if ($hasDocument) {
            $score += 20;
        } else {
            $reasons[] = 'MISSING_DOCUMENT';
        }
        if (FinancialIndicatorStatus::Available === $creditLimitStatus) {
            $score += 20;
        } else {
            $reasons[] = FinancialIndicatorStatus::InconsistentData === $creditLimitStatus ? 'INCONSISTENT_CREDIT_LIMIT' : 'NO_ACTIVE_LIMIT';
        }
        if ($aggregate->receivablesCount > 0) {
            $score += 20;
        } else {
            $reasons[] = 'NO_RECEIVABLE_HISTORY';
        }
        if ($aggregate->paidReceivablesCount >= 3) {
            $score += 20;
        } else {
            $reasons[] = 'INSUFFICIENT_PAID_HISTORY';
        }
        if (null === $lastDataUpdate) {
            $reasons[] = 'UNKNOWN_LAST_UPDATE';
        } else {
            $age = max(0, (int) $lastDataUpdate->diff($referenceDate->toDateTimeImmutable())->format('%r%a'));
            if ($age <= 30) {
                $score += 20;
            } elseif ($age <= 90) {
                $score += 10;
                $reasons[] = 'AGING_FINANCIAL_DATA';
            } else {
                $reasons[] = 'STALE_FINANCIAL_DATA';
            }
        }

        $level = match (true) {
            100 === $score => 'COMPLETE',
            $score >= 75 => 'MINOR_GAPS',
            $score >= 50 => 'INSUFFICIENT_HISTORY',
            default => 'MATERIALLY_INCOMPLETE',
        };

        return new DataQualityResult($score, $level, $reasons);
    }
}
