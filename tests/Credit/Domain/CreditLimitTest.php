<?php

declare(strict_types=1);

namespace App\Tests\Credit\Domain;

use App\Credit\Domain\Entity\CreditLimit;
use App\Credit\Domain\Enum\CreditLimitStatus;
use App\Credit\Domain\ValueObject\MoneyAmount;
use App\Customers\Domain\Entity\Customer;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CreditLimitTest extends TestCase
{
    #[DataProvider('validAmounts')]
    public function testMoneyUsesCanonicalDecimalStrings(string $input, string $expected): void
    {
        self::assertSame($expected, (string) new MoneyAmount($input));
    }

    /** @return iterable<string, array{string, string}> */
    public static function validAmounts(): iterable
    {
        yield 'integer' => ['1', '1.00'];
        yield 'one decimal' => ['42.5', '42.50'];
        yield 'column maximum' => ['99999999999999999.99', '99999999999999999.99'];
    }

    #[DataProvider('invalidAmounts')]
    public function testInvalidMoneyIsRejected(string $amount): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MoneyAmount($amount);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidAmounts(): iterable
    {
        yield 'zero' => ['0'];
        yield 'negative' => ['-1.00'];
        yield 'float-like comma' => ['1,50'];
        yield 'too many decimals' => ['1.001'];
        yield 'column overflow' => ['100000000000000000.00'];
    }

    public function testPeriodReasonAndTenantMustBeValid(): void
    {
        [$organization, $customer, $user] = $this->context();

        try {
            CreditLimit::createActive($organization, $customer, new MoneyAmount('1.00'), new \DateTimeImmutable('2026-08-01'), new \DateTimeImmutable('2026-07-31'), 'reason', $user, new \DateTimeImmutable());
            self::fail('Invalid period should fail.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }

        $this->expectException(\InvalidArgumentException::class);
        CreditLimit::createActive($organization, $customer, new MoneyAmount('1.00'), new \DateTimeImmutable('2026-01-01'), null, ' ', $user, new \DateTimeImmutable());
    }

    public function testExplicitStatusTransitionsPreserveHistory(): void
    {
        [$organization, $customer, $user] = $this->context();
        $draft = CreditLimit::createDraft($organization, $customer, new MoneyAmount('10.00'), new \DateTimeImmutable('2026-01-01'), null, 'draft', new \DateTimeImmutable());
        self::assertSame(CreditLimitStatus::Draft, $draft->status());
        self::assertFalse($draft->isApplicableAt(new \DateTimeImmutable('2026-01-01')));

        $draft->activate($user, new \DateTimeImmutable());
        self::assertSame(CreditLimitStatus::Active, $draft->status());
        $draft->revoke('revoked', new \DateTimeImmutable());
        self::assertSame(CreditLimitStatus::Revoked, $draft->status());
        self::assertFalse($draft->isApplicableAt(new \DateTimeImmutable('2026-01-01')));

        $this->expectException(\DomainException::class);
        $draft->activate($user, new \DateTimeImmutable());
    }

    /** @return array{Organization, Customer, User} */
    private function context(): array
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization', null, null, $now);
        $customer = Customer::create($organization, 'Customer', null, null, null, null, null, $now);
        $user = User::create('User', 'user@example.com', $now);

        return [$organization, $customer, $user];
    }
}
