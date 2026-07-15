<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Service;

use App\Reporting\Domain\Model\ReceivableFinancialAggregate;
use App\Reporting\Domain\ValueObject\DecimalAmount;

final readonly class OverdueExposureCalculator
{
    public function calculate(ReceivableFinancialAggregate $aggregate): DecimalAmount
    {
        return $aggregate->overdueExposure;
    }
}
