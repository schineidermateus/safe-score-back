<?php

declare(strict_types=1);

namespace App\Reporting\Application\DTO;

use App\Reporting\Domain\Model\DataQualityResult;
use App\Reporting\Domain\Model\MoneyResult;
use App\Reporting\Domain\Model\PaymentHistoryResult;
use App\Reporting\Domain\Model\PercentageResult;
use App\Reporting\Domain\ValueObject\DecimalAmount;
use App\Reporting\Domain\ValueObject\ReferenceDate;

final readonly class CustomerFinancialIndicators
{
    public function __construct(
        public int $customerId,
        public int $organizationId,
        public ReferenceDate $referenceDate,
        public string $currency,
        public MoneyResult $creditLimit,
        public DecimalAmount $exposure,
        public MoneyResult $availableCredit,
        public PercentageResult $utilizationPercentage,
        public DecimalAmount $overdueAmount,
        public PercentageResult $overduePercentage,
        public int $maximumOverdueDays,
        public PaymentHistoryResult $paymentHistory,
        public PercentageResult $portfolioConcentrationPercentage,
        public DataQualityResult $dataQuality,
        public ?\DateTimeImmutable $lastDataUpdate,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'reference_date' => (string) $this->referenceDate,
            'currency' => $this->currency,
            'credit_limit' => $this->creditLimit->toArray(),
            'exposure' => (string) $this->exposure,
            'available_credit' => $this->availableCredit->toArray(),
            'utilization_percentage' => $this->utilizationPercentage->toArray(),
            'overdue_amount' => (string) $this->overdueAmount,
            'overdue_percentage' => $this->overduePercentage->toArray(),
            'maximum_overdue_days' => $this->maximumOverdueDays,
            'paid_receivables_count' => $this->paymentHistory->paidReceivablesCount,
            'on_time_paid_receivables_count' => $this->paymentHistory->onTimePaidReceivablesCount,
            'late_paid_receivables_count' => $this->paymentHistory->latePaidReceivablesCount,
            'on_time_payment_percentage' => ['status' => $this->paymentHistory->status->value, 'value' => $this->paymentHistory->onTimePaymentPercentage],
            'average_payment_delay_days' => ['status' => $this->paymentHistory->status->value, 'value' => $this->paymentHistory->averagePaymentDelayDays],
            'maximum_payment_delay_days' => ['status' => $this->paymentHistory->status->value, 'value' => $this->paymentHistory->maximumPaymentDelayDays],
            'portfolio_concentration_percentage' => $this->portfolioConcentrationPercentage->toArray(),
            'data_quality_score' => $this->dataQuality->score,
            'data_quality_level' => $this->dataQuality->level,
            'data_quality_reasons' => $this->dataQuality->reasons,
            'last_data_update' => $this->lastDataUpdate?->format(\DateTimeInterface::ATOM),
        ];
    }
}
