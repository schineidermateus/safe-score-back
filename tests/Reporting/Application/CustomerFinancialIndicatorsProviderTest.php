<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Application;

use App\Authorization\Application\AuthorizationService;
use App\Credit\Application\Service\ActiveCreditLimitResolver;
use App\Credit\Domain\Entity\CreditLimit;
use App\Credit\Domain\ValueObject\MoneyAmount;
use App\Customers\Domain\Entity\Customer;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Reporting\Application\DTO\CustomerScoreInput;
use App\Reporting\Application\DTO\GetCustomerFinancialIndicatorsInput;
use App\Reporting\Application\Provider\CustomerFinancialIndicatorsProvider;
use App\Reporting\Application\Provider\CustomerScoreInputProvider;
use App\Reporting\Application\UseCase\GetCustomerFinancialIndicators;
use App\Reporting\Domain\Enum\FinancialIndicatorStatus;
use App\Reporting\Domain\Model\ReceivableFinancialAggregate;
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
use App\Tests\Credit\Support\InMemoryCreditLimitRepository;
use App\Tests\Customers\Support\InMemoryCustomerRepository;
use App\Tests\Reporting\Support\InMemoryFinancialIndicatorsQuery;
use App\Tests\Support\CurrentContextStub;
use App\Tests\Support\EntityId;
use PHPUnit\Framework\TestCase;

final class CustomerFinancialIndicatorsProviderTest extends TestCase
{
    public function testItComposesKnownFixtureWithOneReferenceDateAndExplicitStates(): void
    {
        [$provider, $query, $organization, $customerA, $customerB, $customerC] = $this->scenario();
        $reference = ReferenceDate::fromString('2026-07-15');

        $a = $provider->getForCustomer($organization, $customerA->requireId(), $reference);
        self::assertSame('50000.00', (string) $a->exposure);
        self::assertSame('50000.00', (string) $a->availableCredit->value);
        self::assertSame('50.00', (string) $a->utilizationPercentage->value);
        self::assertSame('10000.00', (string) $a->overdueAmount);
        self::assertSame('20.00', (string) $a->overduePercentage->value);
        self::assertSame(10, $a->maximumOverdueDays);
        self::assertSame('80.00', $a->paymentHistory->onTimePaymentPercentage);
        self::assertSame('38.46', (string) $a->portfolioConcentrationPercentage->value);
        self::assertSame(100, $a->dataQuality->score);
        self::assertSame('2026-07-15', (string) $a->referenceDate);
        self::assertArrayNotHasKey('organization_id', $a->toArray());
        self::assertSame(['2026-07-15'], $query->referenceDates);

        $b = $provider->getForCustomer($organization, $customerB->requireId(), $reference);
        self::assertSame('-10000.00', (string) $b->availableCredit->value);
        self::assertSame('120.00', (string) $b->utilizationPercentage->value);
        self::assertSame(80, $b->dataQuality->score);
        self::assertContains('INSUFFICIENT_PAID_HISTORY', $b->dataQuality->reasons);

        $c = $provider->getForCustomer($organization, $customerC->requireId(), $reference);
        self::assertSame(FinancialIndicatorStatus::NoActiveLimit, $c->creditLimit->status);
        self::assertNull($c->availableCredit->value);
        self::assertSame(FinancialIndicatorStatus::InsufficientHistory, $c->paymentHistory->status);
        self::assertSame('15.38', (string) $c->portfolioConcentrationPercentage->value);
        self::assertSame(40, $c->dataQuality->score);
        self::assertContains('MISSING_DOCUMENT', $c->dataQuality->reasons);
        self::assertContains('NO_ACTIVE_LIMIT', $c->dataQuality->reasons);
        self::assertSame(0, $query->organizationAggregateCalls);
    }

    public function testBatchUsesOneAggregateMapAndScoreInputHasNoScoreRules(): void
    {
        [$provider, $query, $organization] = $this->scenario();
        $reference = ReferenceDate::fromString('2026-07-15');
        $batch = $provider->getForOrganization($organization, $reference);
        self::assertCount(3, $batch);
        self::assertSame(1, $query->organizationAggregateCalls);
        self::assertSame(0, $query->totalExposureCalls);
        $unit = $provider->getForCustomer($organization, 1, $reference);
        self::assertSame($unit->toArray(), $batch[1]->toArray());
        self::assertSame(1, $query->totalExposureCalls);

        $scoreInputs = (new CustomerScoreInputProvider($provider))->getForOrganization($organization, $reference);
        self::assertContainsOnlyInstancesOf(CustomerScoreInput::class, $scoreInputs);
        self::assertFalse(property_exists(CustomerScoreInput::class, 'score'));
        self::assertFalse(property_exists(CustomerScoreInput::class, 'weight'));
        self::assertSame(2, $query->organizationAggregateCalls);
    }

    public function testCustomerFromAnotherTenantIsNotVisible(): void
    {
        [$provider, , , $customer] = $this->scenario();
        $other = Organization::create('Other', null, null, new \DateTimeImmutable());
        EntityId::assign($other, 2);

        try {
            $provider->getForCustomer($other, $customer->requireId(), ReferenceDate::fromString('2026-07-15'));
            self::fail('Cross-tenant customer must not be visible.');
        } catch (DomainException $exception) {
            self::assertSame('CUSTOMER_NOT_FOUND', $exception->errorCode());
            self::assertSame(404, $exception->statusCode());
        }
    }

    public function testUseCaseValidatesReferenceAndActiveViewerAuthorization(): void
    {
        [$provider, , $organization, $customer] = $this->scenario();
        $now = new \DateTimeImmutable('2026-07-15 12:00:00');
        $viewer = User::create('Viewer', 'viewer-indicators@example.test', $now);
        EntityId::assign($viewer, 99);
        $membership = OrganizationMembership::join($organization, $viewer, MembershipRole::Viewer, $now);
        EntityId::assign($membership, 99);
        $context = new CurrentContextStub($viewer, $organization, $membership);
        $useCase = new GetCustomerFinancialIndicators($provider, $context, new AuthorizationService($context));

        self::assertSame('50000.00', (string) $useCase->execute($customer->requireId(), new GetCustomerFinancialIndicatorsInput('2026-07-15'))->exposure);
        try {
            $useCase->execute($customer->requireId(), new GetCustomerFinancialIndicatorsInput());
            self::fail('Missing reference date must be rejected.');
        } catch (DomainException $exception) {
            self::assertSame('REFERENCE_DATE_REQUIRED', $exception->errorCode());
        }

        $membership->suspend($now);
        $this->expectException(DomainException::class);
        $useCase->execute($customer->requireId(), new GetCustomerFinancialIndicatorsInput('2026-07-15'));
    }

    /** @return array{CustomerFinancialIndicatorsProvider, InMemoryFinancialIndicatorsQuery, Organization, Customer, Customer, Customer} */
    private function scenario(): array
    {
        $now = new \DateTimeImmutable('2026-07-15 12:00:00');
        $organization = Organization::create('Organization', null, null, $now);
        EntityId::assign($organization, 1);
        $user = User::create('Owner', 'owner@example.test', $now);
        EntityId::assign($user, 1);
        $customers = new InMemoryCustomerRepository();
        $customerA = Customer::create($organization, 'Customer A', null, '04252011000110', 'A', null, null, $now);
        $customerB = Customer::create($organization, 'Customer B', null, '11222333000181', 'B', null, null, $now);
        $customerC = Customer::create($organization, 'Customer C', null, null, 'C', null, null, $now);
        foreach ([$customerA, $customerB, $customerC] as $customer) {
            $customers->save($organization, $customer);
        }
        $limits = new InMemoryCreditLimitRepository();
        $limits->save($organization, CreditLimit::createActive($organization, $customerA, new MoneyAmount('100000.00'), new \DateTimeImmutable('2026-01-01'), null, 'Fixture A', $user, $now));
        $limits->save($organization, CreditLimit::createActive($organization, $customerB, new MoneyAmount('50000.00'), new \DateTimeImmutable('2026-01-01'), null, 'Fixture B', $user, $now));

        $query = new InMemoryFinancialIndicatorsQuery();
        $query->aggregates = [
            $customerA->requireId() => new ReceivableFinancialAggregate($customerA->requireId(), new DecimalAmount('50000.00'), new DecimalAmount('10000.00'), 10, 7, 5, 4, 1, 5, 5, $now),
            $customerB->requireId() => new ReceivableFinancialAggregate($customerB->requireId(), new DecimalAmount('60000.00'), DecimalAmount::zero(), 0, 1, 0, 0, 0, 0, 0, $now),
            $customerC->requireId() => new ReceivableFinancialAggregate($customerC->requireId(), new DecimalAmount('20000.00'), DecimalAmount::zero(), 0, 1, 0, 0, 0, 0, 0, $now),
        ];
        $query->lastImport = $now;

        return [new CustomerFinancialIndicatorsProvider(
            $customers,
            $limits,
            new ActiveCreditLimitResolver($limits),
            $query,
            new ExposureCalculator(),
            new CreditAvailabilityCalculator(),
            new CreditUtilizationCalculator(),
            new OverdueExposureCalculator(),
            new OverduePercentageCalculator(),
            new MaximumOverdueDaysCalculator(),
            new PaymentHistoryCalculator(),
            new PortfolioConcentrationCalculator(),
            new DataQualityEvaluator(),
            new LastDataUpdateResolver(),
        ), $query, $organization, $customerA, $customerB, $customerC];
    }
}
