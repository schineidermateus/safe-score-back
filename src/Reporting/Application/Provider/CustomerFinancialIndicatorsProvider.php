<?php

declare(strict_types=1);

namespace App\Reporting\Application\Provider;

use App\Credit\Domain\Entity\CreditLimit;
use App\Credit\Domain\Repository\CreditLimitRepository;
use App\Credit\Domain\Service\ActiveCreditLimitResolverInterface;
use App\Customers\Domain\Entity\Customer;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Domain\Entity\Organization;
use App\Reporting\Application\DTO\CustomerFinancialIndicators;
use App\Reporting\Domain\Enum\FinancialIndicatorStatus;
use App\Reporting\Domain\Model\MoneyResult;
use App\Reporting\Domain\Model\ReceivableFinancialAggregate;
use App\Reporting\Domain\Repository\FinancialIndicatorsQueryInterface;
use App\Reporting\Domain\Service\CreditAvailabilityCalculator;
use App\Reporting\Domain\Service\CreditUtilizationCalculator;
use App\Reporting\Domain\Service\DataQualityEvaluator;
use App\Reporting\Domain\Service\ExposureCalculator;
use App\Reporting\Domain\Service\LastDataUpdateResolver;
use App\Reporting\Domain\Service\MaximumOverdueDaysCalculator;
use App\Reporting\Domain\Service\OverdueExposureCalculator;
use App\Reporting\Domain\Service\OverduePercentageCalculator;
use App\Reporting\Domain\Service\PaymentHistoryCalculator;
use App\Reporting\Domain\Service\PortfolioConcentrationCalculator;
use App\Reporting\Domain\ValueObject\DecimalAmount;
use App\Reporting\Domain\ValueObject\ReferenceDate;
use App\Shared\Domain\Exception\DomainException;

final readonly class CustomerFinancialIndicatorsProvider
{
    public function __construct(
        private CustomerRepository $customers,
        private CreditLimitRepository $creditLimits,
        private ActiveCreditLimitResolverInterface $activeCreditLimitResolver,
        private FinancialIndicatorsQueryInterface $query,
        private ExposureCalculator $exposureCalculator,
        private CreditAvailabilityCalculator $availabilityCalculator,
        private CreditUtilizationCalculator $utilizationCalculator,
        private OverdueExposureCalculator $overdueExposureCalculator,
        private OverduePercentageCalculator $overduePercentageCalculator,
        private MaximumOverdueDaysCalculator $maximumOverdueDaysCalculator,
        private PaymentHistoryCalculator $paymentHistoryCalculator,
        private PortfolioConcentrationCalculator $concentrationCalculator,
        private DataQualityEvaluator $dataQualityEvaluator,
        private LastDataUpdateResolver $lastDataUpdateResolver,
    ) {
    }

    public function getForCustomer(Organization $organization, int $customerId, ReferenceDate $referenceDate): CustomerFinancialIndicators
    {
        $customer = $this->customers->findById($organization, $customerId)
            ?? throw new DomainException('CUSTOMER_NOT_FOUND', 'Cliente não encontrado.', 404);
        $aggregate = $this->query->aggregateForCustomer($organization, $customerId, $referenceDate);
        [$activeLimit, $inconsistent] = $this->resolveActiveLimit($organization, $customer, $referenceDate);

        return $this->compose(
            $organization,
            $customer,
            $referenceDate,
            $aggregate,
            $this->query->totalExposureForOrganization($organization),
            $activeLimit,
            $inconsistent,
            $this->query->lastCreditLimitUpdateForCustomer($organization, $customerId),
            $this->query->lastCompletedFinancialImport($organization),
        );
    }

    /** @return array<int, CustomerFinancialIndicators> */
    public function getForOrganization(Organization $organization, ReferenceDate $referenceDate): array
    {
        $customers = $this->customers->listAll($organization);
        $aggregates = $this->query->aggregatesForOrganization($organization, $referenceDate);
        $totalExposure = $this->totalExposure($aggregates);
        $limitUpdates = $this->query->lastCreditLimitUpdatesForOrganization($organization);
        $lastImport = $this->query->lastCompletedFinancialImport($organization);
        $limits = $this->activeLimitMap($organization, $referenceDate);
        $result = [];

        foreach ($customers as $customer) {
            $customerId = $customer->requireId();
            [$activeLimit, $inconsistent] = $limits[$customerId] ?? [null, false];
            $result[$customerId] = $this->compose(
                $organization,
                $customer,
                $referenceDate,
                $aggregates[$customerId] ?? ReceivableFinancialAggregate::empty($customerId),
                $totalExposure,
                $activeLimit,
                $inconsistent,
                $limitUpdates[$customerId] ?? null,
                $lastImport,
            );
        }

        return $result;
    }

    private function compose(
        Organization $organization,
        Customer $customer,
        ReferenceDate $referenceDate,
        ReceivableFinancialAggregate $aggregate,
        DecimalAmount $organizationExposure,
        ?DecimalAmount $activeLimit,
        bool $inconsistentLimit,
        ?\DateTimeImmutable $lastCreditLimitUpdate,
        ?\DateTimeImmutable $lastImport,
    ): CustomerFinancialIndicators {
        $exposure = $this->exposureCalculator->calculate($aggregate);
        $overdue = $this->overdueExposureCalculator->calculate($aggregate);
        $creditLimit = $this->creditLimitResult($activeLimit, $inconsistentLimit);
        $lastUpdate = $this->lastDataUpdateResolver->resolve($aggregate->lastReceivableUpdate, $lastCreditLimitUpdate, $lastImport);

        return new CustomerFinancialIndicators(
            $customer->requireId(),
            $organization->requireId(),
            $referenceDate,
            $organization->currency(),
            $creditLimit,
            $exposure,
            $this->availabilityCalculator->calculate($activeLimit, $exposure, $inconsistentLimit),
            $this->utilizationCalculator->calculate($activeLimit, $exposure, $inconsistentLimit),
            $overdue,
            $this->overduePercentageCalculator->calculate($overdue, $exposure),
            $this->maximumOverdueDaysCalculator->calculate($aggregate),
            $this->paymentHistoryCalculator->calculate($aggregate),
            $this->concentrationCalculator->calculate($exposure, $organizationExposure),
            $this->dataQualityEvaluator->evaluate(null !== $customer->document(), $creditLimit->status, $aggregate, $lastUpdate, $referenceDate),
            $lastUpdate,
        );
    }

    /** @return array{DecimalAmount|null, bool} */
    private function resolveActiveLimit(Organization $organization, Customer $customer, ReferenceDate $referenceDate): array
    {
        try {
            $limit = $this->activeCreditLimitResolver->resolve($organization, $customer, $referenceDate->toDateTimeImmutable());
        } catch (DomainException $exception) {
            if ('CREDIT_LIMIT_INTEGRITY_ERROR' !== $exception->errorCode()) {
                throw $exception;
            }

            return [null, true];
        }

        return [null === $limit ? null : new DecimalAmount($limit->amount()), false];
    }

    /** @return array<int, array{DecimalAmount|null, bool}> */
    private function activeLimitMap(Organization $organization, ReferenceDate $referenceDate): array
    {
        /** @var array<int, list<CreditLimit>> $grouped */
        $grouped = [];
        foreach ($this->creditLimits->findActiveByOrganizationAndDate($organization, $referenceDate->toDateTimeImmutable()) as $limit) {
            $grouped[$limit->customer()->requireId()][] = $limit;
        }
        $result = [];
        foreach ($grouped as $customerId => $limits) {
            $result[$customerId] = count($limits) > 1
                ? [null, true]
                : [new DecimalAmount($limits[0]->amount()), false];
        }

        return $result;
    }

    private function creditLimitResult(?DecimalAmount $activeLimit, bool $inconsistent): MoneyResult
    {
        if ($inconsistent || null !== $activeLimit && !$activeLimit->isPositive()) {
            return MoneyResult::unavailable(FinancialIndicatorStatus::InconsistentData);
        }

        return null === $activeLimit
            ? MoneyResult::unavailable(FinancialIndicatorStatus::NoActiveLimit)
            : MoneyResult::available($activeLimit);
    }

    /** @param array<int, ReceivableFinancialAggregate> $aggregates */
    private function totalExposure(array $aggregates): DecimalAmount
    {
        $total = DecimalAmount::zero();
        foreach ($aggregates as $aggregate) {
            $total = $total->add($aggregate->exposure);
        }

        return $total;
    }
}
