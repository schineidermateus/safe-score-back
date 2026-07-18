<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Service;

use App\Reporting\Domain\Enum\FinancialIndicatorStatus;
use App\Reporting\Domain\Model\PercentageResult;
use App\Reporting\Domain\ValueObject\DecimalAmount;
use App\Reporting\Domain\ValueObject\DecimalPercentage;

final readonly class PortfolioConcentrationCalculator
{
    public function calculate(DecimalAmount $customerExposure, DecimalAmount $organizationExposure): PercentageResult
    {
        if (!$organizationExposure->isPositive()) {
            return PercentageResult::unavailable(FinancialIndicatorStatus::NoPortfolio);
        }

        return PercentageResult::available(DecimalPercentage::ratio($customerExposure, $organizationExposure));
    }
}
