<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Service;

use App\Reporting\Domain\Enum\FinancialIndicatorStatus;
use App\Reporting\Domain\Model\MoneyResult;
use App\Reporting\Domain\ValueObject\DecimalAmount;

final readonly class CreditAvailabilityCalculator
{
    public function calculate(?DecimalAmount $activeLimit, DecimalAmount $exposure, bool $inconsistent = false): MoneyResult
    {
        if ($inconsistent || null !== $activeLimit && !$activeLimit->isPositive()) {
            return MoneyResult::unavailable(FinancialIndicatorStatus::InconsistentData);
        }
        if (null === $activeLimit) {
            return MoneyResult::unavailable(FinancialIndicatorStatus::NoActiveLimit);
        }

        return MoneyResult::available($activeLimit->subtract($exposure));
    }
}
