<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Domain;

use App\Reporting\Domain\Enum\FinancialIndicatorStatus;
use App\Reporting\Domain\Model\MoneyResult;
use App\Reporting\Domain\Model\ReceivableFinancialAggregate;
use App\Reporting\Domain\Service\CreditAvailabilityCalculator;
use App\Reporting\Domain\Service\CreditUtilizationCalculator;
use App\Reporting\Domain\Service\DataQualityEvaluator;
use App\Reporting\Domain\Service\LastDataUpdateResolver;
use App\Reporting\Domain\Service\MaximumOverdueDaysCalculator;
use App\Reporting\Domain\Service\OverduePercentageCalculator;
use App\Reporting\Domain\Service\PaymentHistoryCalculator;
use App\Reporting\Domain\Service\PortfolioConcentrationCalculator;
use App\Reporting\Domain\ValueObject\DecimalAmount;
use App\Reporting\Domain\ValueObject\ReferenceDate;
use PHPUnit\Framework\TestCase;

final class FinancialCalculatorsTest extends TestCase
{
    public function testMoneyAndPercentagesAreExactAndNeverUseFloat(): void
    {
        self::assertSame('0.30', (string) (new DecimalAmount('0.10'))->add(new DecimalAmount('0.20')));
        $availability = (new CreditAvailabilityCalculator())->calculate(new DecimalAmount('50000.00'), new DecimalAmount('60000.00'));
        self::assertSame('-10000.00', (string) $availability->value);
        self::assertSame('120.00', (string) (new CreditUtilizationCalculator())->calculate(new DecimalAmount('50000.00'), new DecimalAmount('60000.00'))->value);
        self::assertSame('50.00', (string) (new CreditUtilizationCalculator())->calculate(new DecimalAmount('100000.00'), new DecimalAmount('50000.00'))->value);
        self::assertSame('100.00', (string) (new CreditUtilizationCalculator())->calculate(new DecimalAmount('100.00'), new DecimalAmount('100.00'))->value);
        self::assertSame('0.00', (string) (new CreditUtilizationCalculator())->calculate(new DecimalAmount('100.00'), DecimalAmount::zero())->value);
    }

    public function testUnavailableStatesAreExplicit(): void
    {
        $availability = new CreditAvailabilityCalculator();
        $utilization = new CreditUtilizationCalculator();
        self::assertSame(FinancialIndicatorStatus::NoActiveLimit, $availability->calculate(null, DecimalAmount::zero())->status);
        self::assertSame(FinancialIndicatorStatus::NoActiveLimit, $utilization->calculate(null, DecimalAmount::zero())->status);
        self::assertSame(FinancialIndicatorStatus::InconsistentData, $utilization->calculate(new DecimalAmount('0.00'), DecimalAmount::zero())->status);
        self::assertSame(FinancialIndicatorStatus::NoExposure, (new OverduePercentageCalculator())->calculate(DecimalAmount::zero(), DecimalAmount::zero())->status);
        self::assertSame(FinancialIndicatorStatus::NoPortfolio, (new PortfolioConcentrationCalculator())->calculate(DecimalAmount::zero(), DecimalAmount::zero())->status);
    }

    public function testOverdueAndConcentrationRulesUseExplicitDenominators(): void
    {
        self::assertSame('20.00', (string) (new OverduePercentageCalculator())->calculate(new DecimalAmount('10000.00'), new DecimalAmount('50000.00'))->value);
        self::assertSame('38.46', (string) (new PortfolioConcentrationCalculator())->calculate(new DecimalAmount('50000.00'), new DecimalAmount('130000.00'))->value);
        self::assertSame('0.00', (string) (new PortfolioConcentrationCalculator())->calculate(DecimalAmount::zero(), new DecimalAmount('1.00'))->value);
        self::assertSame(10, (new MaximumOverdueDaysCalculator())->calculate($this->aggregate(maximumOverdueDays: 10)));
        self::assertSame(0, (new MaximumOverdueDaysCalculator())->calculate($this->aggregate(maximumOverdueDays: 0)));
    }

    public function testPaymentHistoryUsesFinalSettlementAndDoesNotInventHistory(): void
    {
        $calculator = new PaymentHistoryCalculator();
        $empty = $calculator->calculate($this->aggregate());
        self::assertSame(FinancialIndicatorStatus::InsufficientHistory, $empty->status);
        self::assertNull($empty->onTimePaymentPercentage);
        self::assertNull($empty->averagePaymentDelayDays);

        $mixed = $calculator->calculate($this->aggregate(paid: 5, onTime: 4, late: 1, totalDelay: 5, maximumDelay: 5));
        self::assertSame('80.00', $mixed->onTimePaymentPercentage);
        self::assertSame('1.00', $mixed->averagePaymentDelayDays);
        self::assertSame(5, $mixed->maximumPaymentDelayDays);
    }

    public function testDataQualityHasObjectiveWeightsAndReasons(): void
    {
        $reference = ReferenceDate::fromString('2026-07-15');
        $evaluator = new DataQualityEvaluator();
        $complete = $evaluator->evaluate(true, FinancialIndicatorStatus::Available, $this->aggregate(receivables: 5, paid: 3), new \DateTimeImmutable('2026-07-10'), $reference);
        self::assertSame(100, $complete->score);
        self::assertSame([], $complete->reasons);

        $incomplete = $evaluator->evaluate(false, FinancialIndicatorStatus::NoActiveLimit, $this->aggregate(), new \DateTimeImmutable('2026-01-01'), $reference);
        self::assertSame(0, $incomplete->score);
        self::assertContains('MISSING_DOCUMENT', $incomplete->reasons);
        self::assertContains('NO_ACTIVE_LIMIT', $incomplete->reasons);
        self::assertContains('STALE_FINANCIAL_DATA', $incomplete->reasons);
    }

    public function testReferenceDateIsAStableCivilDate(): void
    {
        $reference = ReferenceDate::fromString('2026-07-15');
        self::assertSame('2026-07-15', (string) $reference);
        self::assertSame(0, $reference->overdueDaysSince(new \DateTimeImmutable('2026-07-15')));
        self::assertSame(1, $reference->overdueDaysSince(new \DateTimeImmutable('2026-07-14')));
    }

    public function testInvalidReferenceDateIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ReferenceDate::fromString('15/07/2026');
    }

    public function testUnavailableResultCannotClaimToBeAvailable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MoneyResult::unavailable(FinancialIndicatorStatus::Available);
    }

    public function testLastDataUpdateIsTheLatestSystemTimestamp(): void
    {
        $result = (new LastDataUpdateResolver())->resolve(
            new \DateTimeImmutable('2026-07-10T10:00:00+00:00'),
            null,
            new \DateTimeImmutable('2026-07-12T09:00:00+00:00'),
        );
        self::assertSame('2026-07-12T09:00:00+00:00', $result?->format(\DateTimeInterface::ATOM));
    }

    private function aggregate(
        int $maximumOverdueDays = 0,
        int $receivables = 0,
        int $paid = 0,
        int $onTime = 0,
        int $late = 0,
        int $totalDelay = 0,
        int $maximumDelay = 0,
    ): ReceivableFinancialAggregate {
        return new ReceivableFinancialAggregate(1, DecimalAmount::zero(), DecimalAmount::zero(), $maximumOverdueDays, $receivables, $paid, $onTime, $late, $totalDelay, $maximumDelay, null);
    }
}
