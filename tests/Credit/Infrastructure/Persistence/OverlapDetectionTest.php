<?php

declare(strict_types=1);

namespace App\Tests\Credit\Infrastructure\Persistence;

use App\Credit\Domain\Entity\CreditLimit;
use App\Credit\Domain\ValueObject\MoneyAmount;
use App\Customers\Domain\Entity\Customer;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Tests\Credit\Support\InMemoryCreditLimitRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OverlapDetectionTest extends TestCase
{
    #[DataProvider('periods')]
    public function testInclusiveOverlapRules(string $from, ?string $until, bool $expected): void
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization', null, null, $now);
        $customer = Customer::create($organization, 'Customer', null, null, null, null, null, $now);
        $user = User::create('User', 'user@example.com', $now);
        $existing = CreditLimit::createActive($organization, $customer, new MoneyAmount('1.00'), new \DateTimeImmutable('2026-07-01'), new \DateTimeImmutable('2026-07-31'), 'reason', $user, $now);
        $repository = new InMemoryCreditLimitRepository();
        $repository->save($organization, $existing);

        self::assertSame($expected, $repository->existsOverlappingActivePeriod(
            $customer,
            $organization,
            new \DateTimeImmutable($from),
            null === $until ? null : new \DateTimeImmutable($until),
        ));
        self::assertFalse($repository->existsOverlappingActivePeriod(
            $customer,
            $organization,
            new \DateTimeImmutable('2026-07-01'),
            new \DateTimeImmutable('2026-07-31'),
            $existing->requireId(),
        ));
    }

    /** @return iterable<string, array{string, ?string, bool}> */
    public static function periods(): iterable
    {
        yield 'equal' => ['2026-07-01', '2026-07-31', true];
        yield 'contained' => ['2026-07-10', '2026-07-20', true];
        yield 'contains' => ['2026-06-01', '2026-08-31', true];
        yield 'partial start' => ['2026-06-15', '2026-07-01', true];
        yield 'partial end' => ['2026-07-31', '2026-08-15', true];
        yield 'adjacent before' => ['2026-06-01', '2026-06-30', false];
        yield 'adjacent after' => ['2026-08-01', '2026-08-31', false];
        yield 'open overlap' => ['2026-07-10', null, true];
        yield 'open after' => ['2026-08-01', null, false];
    }

    public function testTwoOpenPeriodsOverlap(): void
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization', null, null, $now);
        $customer = Customer::create($organization, 'Customer', null, null, null, null, null, $now);
        $user = User::create('User', 'user@example.com', $now);
        $repository = new InMemoryCreditLimitRepository();
        $repository->save($organization, CreditLimit::createActive(
            $organization,
            $customer,
            new MoneyAmount('1.00'),
            new \DateTimeImmutable('2026-07-01'),
            null,
            'reason',
            $user,
            $now,
        ));

        self::assertTrue($repository->existsOverlappingActivePeriod(
            $customer,
            $organization,
            new \DateTimeImmutable('2027-01-01'),
            null,
        ));
    }
}
