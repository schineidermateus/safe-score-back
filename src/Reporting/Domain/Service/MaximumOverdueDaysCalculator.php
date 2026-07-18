<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Service;

use App\Reporting\Domain\Model\ReceivableFinancialAggregate;

final readonly class MaximumOverdueDaysCalculator
{
    public function calculate(ReceivableFinancialAggregate $aggregate): int
    {
        return max(0, $aggregate->maximumOverdueDays);
    }
}
