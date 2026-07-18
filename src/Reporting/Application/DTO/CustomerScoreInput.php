<?php

declare(strict_types=1);

namespace App\Reporting\Application\DTO;

use App\Reporting\Domain\Model\MoneyResult;
use App\Reporting\Domain\Model\PercentageResult;
use App\Reporting\Domain\ValueObject\DecimalAmount;
use App\Reporting\Domain\ValueObject\ReferenceDate;

final readonly class CustomerScoreInput
{
    public function __construct(
        public int $customerId,
        public int $organizationId,
        public ReferenceDate $referenceDate,
        public MoneyResult $creditLimit,
        public DecimalAmount $exposure,
        public PercentageResult $utilizationPercentage,
        public DecimalAmount $overdueAmount,
        public PercentageResult $overduePercentage,
        public int $maximumOverdueDays,
        public int $paidReceivablesCount,
        public ?string $onTimePaymentPercentage,
        public ?string $averagePaymentDelayDays,
        public ?int $maximumPaymentDelayDays,
        public PercentageResult $portfolioConcentrationPercentage,
        public int $dataQualityScore,
        public ?\DateTimeImmutable $lastDataUpdate,
    ) {
    }

    public static function fromIndicators(CustomerFinancialIndicators $indicators): self
    {
        return new self(
            $indicators->customerId,
            $indicators->organizationId,
            $indicators->referenceDate,
            $indicators->creditLimit,
            $indicators->exposure,
            $indicators->utilizationPercentage,
            $indicators->overdueAmount,
            $indicators->overduePercentage,
            $indicators->maximumOverdueDays,
            $indicators->paymentHistory->paidReceivablesCount,
            $indicators->paymentHistory->onTimePaymentPercentage,
            $indicators->paymentHistory->averagePaymentDelayDays,
            $indicators->paymentHistory->maximumPaymentDelayDays,
            $indicators->portfolioConcentrationPercentage,
            $indicators->dataQuality->score,
            $indicators->lastDataUpdate,
        );
    }
}
