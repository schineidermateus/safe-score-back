<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Service;

use App\Reporting\Domain\Enum\FinancialIndicatorStatus;
use App\Reporting\Domain\Model\PercentageResult;
use App\Reporting\Domain\ValueObject\DecimalAmount;
use App\Reporting\Domain\ValueObject\DecimalPercentage;

final readonly class CreditUtilizationCalculator
{
    public function calculate(?DecimalAmount $activeLimit, DecimalAmount $exposure, bool $inconsistent = false): PercentageResult
    {
        if ($inconsistent || null !== $activeLimit && !$activeLimit->isPositive()) {
            return PercentageResult::unavailable(FinancialIndicatorStatus::InconsistentData);
        }
        if (null === $activeLimit) {
            return PercentageResult::unavailable(FinancialIndicatorStatus::NoActiveLimit);
        }

        return PercentageResult::available(DecimalPercentage::ratio($exposure, $activeLimit));
    }
}
