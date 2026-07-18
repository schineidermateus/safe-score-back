<?php

declare(strict_types=1);

namespace App\Tests\Receivables\Domain;

use App\Customers\Domain\Entity\Customer;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Receivables\Application\Service\ReceivableStatusResolver;
use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\Enum\AgingBucket;
use App\Receivables\Domain\Enum\ReceivableStatus;
use App\Receivables\Domain\Service\AgingClassifier;
use App\Receivables\Domain\ValueObject\ReceivableAmount;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReceivableTest extends TestCase
{
    #[DataProvider('validAmounts')]
    public function testMoneyIsCanonicalAndExact(string $input, string $expected): void
    {
        self::assertSame($expected, (string) new ReceivableAmount($input));
        self::assertSame('0.30', (string) (new ReceivableAmount('0.10'))->add(new ReceivableAmount('0.20')));
    }

    /** @return iterable<string, array{string, string}> */
    public static function validAmounts(): iterable
    {
        yield 'zero' => ['0', '0.00'];
        yield 'fraction' => ['42.5', '42.50'];
        yield 'maximum' => ['99999999999999999.99', '99999999999999999.99'];
    }

    #[DataProvider('invalidAmounts')]
    public function testInvalidMoneyIsRejected(string $amount): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ReceivableAmount($amount);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidAmounts(): iterable
    {
        yield 'negative' => ['-1.00'];
        yield 'comma' => ['1,50'];
        yield 'precision' => ['1.001'];
        yield 'overflow' => ['100000000000000000.00'];
    }

    public function testPartialAndCompletePaymentsKeepBalancesAndEffectivePaymentDate(): void
    {
        [$receivable, $user] = $this->receivable('100.00', '2026-07-31');
        $first = $receivable->registerPayment(new ReceivableAmount('30.10'), new \DateTimeImmutable('2026-07-15'), $user, new \DateTimeImmutable());
        self::assertSame('30.10', $first->amount());
        self::assertSame('69.90', $receivable->openAmount());
        self::assertSame('30.10', $receivable->paidAmount());
        self::assertSame(ReceivableStatus::PartiallyPaid, $receivable->status());
        $receivable->registerPayment(new ReceivableAmount('69.90'), new \DateTimeImmutable('2026-08-01'), $user, new \DateTimeImmutable());
        self::assertSame('0.00', $receivable->openAmount());
        self::assertSame('100.00', $receivable->paidAmount());
        self::assertSame(ReceivableStatus::Paid, $receivable->status());
        $paymentDate = $receivable->paymentDate();
        self::assertInstanceOf(\DateTimeImmutable::class, $paymentDate);
        self::assertSame('2026-08-01', $paymentDate->format('Y-m-d'));
    }

    public function testExcessivePaymentAndPaymentOnCancelledAreRejected(): void
    {
        [$receivable, $user] = $this->receivable('100.00', '2026-07-31');
        try {
            $receivable->registerPayment(new ReceivableAmount('100.01'), new \DateTimeImmutable(), $user, new \DateTimeImmutable());
            self::fail();
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }
        $receivable->cancel($user, 'duplicated', new \DateTimeImmutable());
        $this->expectException(\DomainException::class);
        $receivable->registerPayment(new ReceivableAmount('1.00'), new \DateTimeImmutable(), $user, new \DateTimeImmutable());
    }

    public function testPaidAndCancelledReceivablesDoNotHaveAgingBuckets(): void
    {
        [$paid, $user] = $this->receivable('10.00', '2026-01-01');
        $paid->registerPayment(new ReceivableAmount('10.00'), new \DateTimeImmutable('2026-01-02'), $user, new \DateTimeImmutable());
        [$cancelled] = $this->receivable('10.00', '2026-01-01');
        $cancelled->cancel($user, 'cancelled', new \DateTimeImmutable());
        $resolver = new ReceivableStatusResolver();
        $classifier = new AgingClassifier($resolver);
        self::assertSame(ReceivableStatus::Paid, $resolver->resolve($paid, new \DateTimeImmutable('2026-07-15')));
        self::assertSame(ReceivableStatus::Cancelled, $resolver->resolve($cancelled, new \DateTimeImmutable('2026-07-15')));
        self::assertNull($classifier->classify($paid, new \DateTimeImmutable('2026-07-15')));
        self::assertNull($classifier->classify($cancelled, new \DateTimeImmutable('2026-07-15')));
    }

    public function testOverdueDerivedStatusTakesPrecedenceOverPartialPaymentAndChangesWithReferenceDate(): void
    {
        [$receivable, $user] = $this->receivable('100.00', '2026-07-15');
        $receivable->registerPayment(new ReceivableAmount('25.00'), new \DateTimeImmutable('2026-07-10'), $user, new \DateTimeImmutable());
        $resolver = new ReceivableStatusResolver();

        self::assertSame(ReceivableStatus::PartiallyPaid, $resolver->resolve($receivable, new \DateTimeImmutable('2026-07-15')));
        self::assertSame(ReceivableStatus::Overdue, $resolver->resolve($receivable, new \DateTimeImmutable('2026-07-16')));
        self::assertSame(ReceivableStatus::PartiallyPaid, $receivable->status());
    }

    public function testCustomerFromAnotherTenantCannotBeAssociated(): void
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization A', null, null, $now);
        $otherOrganization = Organization::create('Organization B', null, null, $now);
        $customer = Customer::create($otherOrganization, 'Customer', null, null, null, null, null, $now);
        $this->expectException(\DomainException::class);
        Receivable::create($organization, $customer, 'MANUAL', null, 'DOC', $now, $now, new ReceivableAmount('1.00'), $now);
    }

    #[DataProvider('agingCases')]
    public function testStatusAndAgingAreReferenceDateDriven(string $due, ReceivableStatus $status, AgingBucket $bucket): void
    {
        [$receivable] = $this->receivable('100.00', $due);
        $resolver = new ReceivableStatusResolver();
        self::assertSame($status, $resolver->resolve($receivable, new \DateTimeImmutable('2026-07-15')));
        self::assertSame($bucket, (new AgingClassifier($resolver))->classify($receivable, new \DateTimeImmutable('2026-07-15')));
    }

    /** @return iterable<string, array{string, ReceivableStatus, AgingBucket}> */
    public static function agingCases(): iterable
    {
        yield '0 upcoming' => ['2026-07-15', ReceivableStatus::Open, AgingBucket::Upcoming];
        yield '1 day' => ['2026-07-14', ReceivableStatus::Overdue, AgingBucket::Days1To15];
        yield '15 days' => ['2026-06-30', ReceivableStatus::Overdue, AgingBucket::Days1To15];
        yield '16 days' => ['2026-06-29', ReceivableStatus::Overdue, AgingBucket::Days16To30];
        yield '30 days' => ['2026-06-15', ReceivableStatus::Overdue, AgingBucket::Days16To30];
        yield '31 days' => ['2026-06-14', ReceivableStatus::Overdue, AgingBucket::Days31To60];
        yield '60 days' => ['2026-05-16', ReceivableStatus::Overdue, AgingBucket::Days31To60];
        yield '61 days' => ['2026-05-15', ReceivableStatus::Overdue, AgingBucket::Days61To90];
        yield '90 days' => ['2026-04-16', ReceivableStatus::Overdue, AgingBucket::Days61To90];
        yield '91 days' => ['2026-04-15', ReceivableStatus::Overdue, AgingBucket::Over90];
    }

    /** @return array{Receivable, User} */
    private function receivable(string $amount, string $due): array
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization', null, null, $now);
        $customer = Customer::create($organization, 'Customer', null, null, null, null, null, $now);
        $user = User::create('User', 'user@example.com', $now);

        return [Receivable::create($organization, $customer, 'MANUAL', null, 'DOC-1', new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable($due), new ReceivableAmount($amount), $now), $user];
    }
}
