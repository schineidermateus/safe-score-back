<?php

declare(strict_types=1);

namespace App\Tests\Credit\Application;

use App\Credit\Application\Service\ActiveCreditLimitResolver;
use App\Credit\Domain\Entity\CreditLimit;
use App\Credit\Domain\ValueObject\MoneyAmount;
use App\Customers\Domain\Entity\Customer;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Shared\Domain\Exception\DomainException;
use App\Tests\Credit\Support\InMemoryCreditLimitRepository;
use PHPUnit\Framework\TestCase;

final class ActiveCreditLimitResolverTest extends TestCase
{
    public function testItResolvesOnlyAnActiveLimitInsideInclusivePeriod(): void
    {
        [$repository, $organization, $customer, $user] = $this->context();
        $limit = $this->active($organization, $customer, $user, '2026-07-01', '2026-07-31');
        $repository->save($organization, $limit);
        $resolver = new ActiveCreditLimitResolver($repository);

        self::assertNull($resolver->resolve($organization, $customer, new \DateTimeImmutable('2026-06-30')));
        self::assertSame($limit, $resolver->resolve($organization, $customer, new \DateTimeImmutable('2026-07-01')));
        self::assertSame($limit, $resolver->resolve($organization, $customer, new \DateTimeImmutable('2026-07-31')));
        self::assertNull($resolver->resolve($organization, $customer, new \DateTimeImmutable('2026-08-01')));
    }

    public function testOpenEndedLimitAndAbsenceAreExplicit(): void
    {
        [$repository, $organization, $customer, $user] = $this->context();
        $resolver = new ActiveCreditLimitResolver($repository);
        self::assertNull($resolver->resolve($organization, $customer, new \DateTimeImmutable('2026-01-01')));

        $limit = $this->active($organization, $customer, $user, '2026-01-01', null);
        $repository->save($organization, $limit);
        self::assertSame($limit, $resolver->resolve($organization, $customer, new \DateTimeImmutable('2099-01-01')));
    }

    public function testDraftRevokedAndExpiredAreIgnored(): void
    {
        [$repository, $organization, $customer, $user] = $this->context();
        $draft = CreditLimit::createDraft($organization, $customer, new MoneyAmount('10.00'), new \DateTimeImmutable('2026-01-01'), null, 'draft', new \DateTimeImmutable());
        $revoked = $this->active($organization, $customer, $user, '2026-01-01', null);
        $revoked->revoke('revoked', new \DateTimeImmutable());
        $expired = $this->active($organization, $customer, $user, '2026-01-01', null);
        $expired->expire(new \DateTimeImmutable());
        foreach ([$draft, $revoked, $expired] as $limit) {
            $repository->save($organization, $limit);
        }

        self::assertNull((new ActiveCreditLimitResolver($repository))->resolve($organization, $customer, new \DateTimeImmutable('2026-07-01')));
    }

    public function testItDetectsConflictingApplicableLimits(): void
    {
        [$repository, $organization, $customer, $user] = $this->context();
        $repository->save($organization, $this->active($organization, $customer, $user, '2026-01-01', null));
        $repository->save($organization, $this->active($organization, $customer, $user, '2026-02-01', null));

        try {
            (new ActiveCreditLimitResolver($repository))->resolve($organization, $customer, new \DateTimeImmutable('2026-03-01'));
            self::fail('Conflicting active limits should fail.');
        } catch (DomainException $exception) {
            self::assertSame('CREDIT_LIMIT_INTEGRITY_ERROR', $exception->errorCode());
        }
    }

    /** @return array{InMemoryCreditLimitRepository, Organization, Customer, User} */
    private function context(): array
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization', null, null, $now);
        $customer = Customer::create($organization, 'Customer', null, null, null, null, null, $now);
        $user = User::create('User', 'user@example.com', $now);

        return [new InMemoryCreditLimitRepository(), $organization, $customer, $user];
    }

    private function active(Organization $organization, Customer $customer, User $user, string $from, ?string $until): CreditLimit
    {
        return CreditLimit::createActive($organization, $customer, new MoneyAmount('10.00'), new \DateTimeImmutable($from), null === $until ? null : new \DateTimeImmutable($until), 'reason', $user, new \DateTimeImmutable());
    }
}
