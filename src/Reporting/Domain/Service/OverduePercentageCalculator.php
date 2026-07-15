<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Service;

use App\Reporting\Domain\Enum\FinancialIndicatorStatus;
use App\Reporting\Domain\Model\PercentageResult;
use App\Reporting\Domain\ValueObject\DecimalAmount;
use App\Reporting\Domain\ValueObject\DecimalPercentage;

final readonly class OverduePercentageCalculator
{
    public function calculate(DecimalAmount $overdue, DecimalAmount $exposure): PercentageResult
    {
        if (!$exposure->isPositive()) {
            return PercentageResult::unavailable(FinancialIndicatorStatus::NoExposure);
        }

        return PercentageResult::available(DecimalPercentage::ratio($overdue, $exposure));
    }
}
