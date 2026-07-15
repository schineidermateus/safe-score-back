<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixtures;

use App\Credit\Domain\Entity\CreditLimit;
use App\Credit\Domain\ValueObject\MoneyAmount;
use App\Customers\Domain\Entity\Customer;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\ValueObject\ReceivableAmount;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();
        $organizationA = Organization::create('SafeScore Demo LTDA', 'SafeScore Demo', '04252011000110', $now);
        $organizationB = Organization::create('Segunda Organização LTDA', 'Organização B', '11222333000181', $now);

        $owner = User::create('Usuário Desenvolvimento', 'dev@safescore.local', $now);
        $admin = User::create('Administrador', 'admin@safescore.local', $now);
        $analyst = User::create('Analista', 'analyst@safescore.local', $now);
        $viewer = User::create('Consulta', 'viewer@safescore.local', $now);
        $secondOwner = User::create('Owner Organização B', 'owner-b@safescore.local', $now);

        foreach ([$organizationA, $organizationB, $owner, $admin, $analyst, $viewer, $secondOwner] as $entity) {
            $manager->persist($entity);
        }

        $baseCustomerA = Customer::create($organizationA, 'Cliente Organização A', 'Cliente A', '04252011000110', 'A-001', null, null, $now);
        $baseCustomerB = Customer::create($organizationB, 'Cliente Organização B', 'Cliente B', '04252011000110', 'B-001', null, null, $now);

        foreach ([
            OrganizationMembership::join($organizationA, $owner, MembershipRole::Owner, $now),
            OrganizationMembership::join($organizationA, $admin, MembershipRole::Admin, $now),
            OrganizationMembership::join($organizationA, $analyst, MembershipRole::Analyst, $now),
            OrganizationMembership::join($organizationA, $viewer, MembershipRole::Viewer, $now),
            OrganizationMembership::join($organizationB, $secondOwner, MembershipRole::Owner, $now),
            $baseCustomerA,
            $baseCustomerB,
        ] as $entity) {
            $manager->persist($entity);
        }

        $activeCustomer = Customer::create($organizationA, 'Cliente com limite ativo', 'Limite Ativo', null, 'A-101', null, null, $now);
        $expiredCustomer = Customer::create($organizationA, 'Cliente com limite expirado', 'Limite Expirado', null, 'A-102', null, null, $now);
        $revokedCustomer = Customer::create($organizationA, 'Cliente com limite revogado', 'Limite Revogado', null, 'A-103', null, null, $now);
        $withoutLimitCustomer = Customer::create($organizationA, 'Cliente sem limite', 'Sem Limite', null, 'A-104', null, null, $now);
        $otherTenantCustomer = Customer::create($organizationB, 'Cliente B com limite ativo', 'Limite B', null, 'B-101', null, null, $now);

        foreach ([$activeCustomer, $expiredCustomer, $revokedCustomer, $withoutLimitCustomer, $otherTenantCustomer] as $customer) {
            $manager->persist($customer);
        }

        $activeLimit = CreditLimit::createActive(
            $organizationA,
            $activeCustomer,
            new MoneyAmount('250000.00'),
            new \DateTimeImmutable('2026-01-01'),
            null,
            'Limite comercial vigente para demonstração.',
            $owner,
            $now,
        );
        $activeCustomerHistory = CreditLimit::createActive(
            $organizationA,
            $activeCustomer,
            new MoneyAmount('125000.50'),
            new \DateTimeImmutable('2025-01-01'),
            new \DateTimeImmutable('2025-12-31'),
            'Limite anterior sem sobreposição.',
            $owner,
            $now,
        );
        $activeCustomerHistory->expire($now);
        $expiredLimit = CreditLimit::createActive(
            $organizationA,
            $expiredCustomer,
            new MoneyAmount('100000.00'),
            new \DateTimeImmutable('2025-01-01'),
            new \DateTimeImmutable('2025-12-31'),
            'Limite histórico para demonstração.',
            $owner,
            $now,
        );
        $expiredLimit->expire($now);
        $revokedLimit = CreditLimit::createActive(
            $organizationA,
            $revokedCustomer,
            new MoneyAmount('75000.00'),
            new \DateTimeImmutable('2026-01-01'),
            null,
            'Limite criado para demonstração de revogação.',
            $owner,
            $now,
        );
        $revokedLimit->revoke('Revogado para demonstrar o histórico.', $now);
        $otherTenantLimit = CreditLimit::createActive(
            $organizationB,
            $otherTenantCustomer,
            new MoneyAmount('90000.00'),
            new \DateTimeImmutable('2026-01-01'),
            null,
            'Limite isolado da segunda organização.',
            $secondOwner,
            $now,
        );

        foreach ([$activeLimit, $activeCustomerHistory, $expiredLimit, $revokedLimit, $otherTenantLimit] as $creditLimit) {
            $manager->persist($creditLimit);
        }

        $receivables = [
            Receivable::create($organizationA, $baseCustomerA, 'FIXTURE', 'SHARED-001', 'OPEN-UPCOMING', new \DateTimeImmutable('2026-07-01'), new \DateTimeImmutable('2026-08-15'), new ReceivableAmount('1000.00'), $now),
            Receivable::create($organizationA, $baseCustomerA, 'FIXTURE', 'PARTIAL-001', 'PARTIALLY-PAID', new \DateTimeImmutable('2026-06-01'), new \DateTimeImmutable('2026-08-01'), new ReceivableAmount('2000.00'), $now),
            Receivable::create($organizationA, $baseCustomerA, 'FIXTURE', 'PAID-ONTIME', 'PAID-ONTIME', new \DateTimeImmutable('2026-05-01'), new \DateTimeImmutable('2026-06-30'), new ReceivableAmount('3000.00'), $now),
            Receivable::create($organizationA, $baseCustomerA, 'FIXTURE', 'PAID-LATE', 'PAID-LATE', new \DateTimeImmutable('2026-04-01'), new \DateTimeImmutable('2026-05-31'), new ReceivableAmount('4000.00'), $now),
            Receivable::create($organizationA, $baseCustomerA, 'FIXTURE', 'AGING-15', 'AGING-1-15', new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-07-05'), new ReceivableAmount('500.00'), $now),
            Receivable::create($organizationA, $baseCustomerA, 'FIXTURE', 'AGING-30', 'AGING-16-30', new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-06-20'), new ReceivableAmount('600.00'), $now),
            Receivable::create($organizationA, $baseCustomerA, 'FIXTURE', 'AGING-60', 'AGING-31-60', new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-05-20'), new ReceivableAmount('700.00'), $now),
            Receivable::create($organizationA, $baseCustomerA, 'FIXTURE', 'AGING-90', 'AGING-61-90', new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-04-20'), new ReceivableAmount('800.00'), $now),
            Receivable::create($organizationA, $baseCustomerA, 'FIXTURE', 'AGING-OVER-90', 'AGING-OVER-90', new \DateTimeImmutable('2025-01-01'), new \DateTimeImmutable('2026-01-01'), new ReceivableAmount('900.00'), $now),
            Receivable::create($organizationA, $baseCustomerA, 'FIXTURE', 'CANCELLED-001', 'CANCELLED', new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-12-31'), new ReceivableAmount('100.00'), $now),
            Receivable::create($organizationB, $baseCustomerB, 'FIXTURE', 'SHARED-001', 'SAME-KEY-OTHER-TENANT', new \DateTimeImmutable('2026-07-01'), new \DateTimeImmutable('2026-08-15'), new ReceivableAmount('1100.00'), $now),
        ];
        $payments = [
            $receivables[1]->registerPayment(new ReceivableAmount('750.00'), new \DateTimeImmutable('2026-07-10'), $owner, $now),
            $receivables[2]->registerPayment(new ReceivableAmount('3000.00'), new \DateTimeImmutable('2026-06-30'), $owner, $now),
            $receivables[3]->registerPayment(new ReceivableAmount('1500.00'), new \DateTimeImmutable('2026-06-05'), $owner, $now),
            $receivables[3]->registerPayment(new ReceivableAmount('2500.00'), new \DateTimeImmutable('2026-06-15'), $owner, $now),
        ];
        $receivables[9]->cancel($owner, 'Cenário de demonstração cancelado.', $now);
        foreach ([...$receivables, ...$payments] as $entity) {
            $manager->persist($entity);
        }

        $indicatorNow = new \DateTimeImmutable('2026-07-15 12:00:00');
        $indicatorOrganization = Organization::create('SafeScore Indicadores LTDA', 'Fixtures Indicadores', '53113791000122', $indicatorNow);
        $indicatorOtherOrganization = Organization::create('SafeScore Indicadores Tenant B LTDA', 'Fixtures Tenant B', '62492044000109', $indicatorNow);
        $indicatorOwner = User::create('Owner Fixtures Indicadores', 'indicators@safescore.local', $indicatorNow);
        $indicatorOtherOwner = User::create('Owner Fixtures Indicadores B', 'indicators-b@safescore.local', $indicatorNow);
        foreach ([$indicatorOrganization, $indicatorOtherOrganization, $indicatorOwner, $indicatorOtherOwner] as $entity) {
            $manager->persist($entity);
        }
        $manager->persist(OrganizationMembership::join($indicatorOrganization, $indicatorOwner, MembershipRole::Owner, $indicatorNow));
        $manager->persist(OrganizationMembership::join($indicatorOtherOrganization, $indicatorOtherOwner, MembershipRole::Owner, $indicatorNow));

        $indicatorA = Customer::create($indicatorOrganization, 'Indicadores Cliente A', 'Indicador A', '52998224725', 'IND-A', null, null, $indicatorNow);
        $indicatorB = Customer::create($indicatorOrganization, 'Indicadores Cliente B', 'Indicador B', '39053344705', 'IND-B', null, null, $indicatorNow);
        $indicatorC = Customer::create($indicatorOrganization, 'Indicadores Cliente C', 'Indicador C', null, 'IND-C', null, null, $indicatorNow);
        $indicatorOtherTenant = Customer::create($indicatorOtherOrganization, 'Indicadores Outro Tenant', 'Indicador Tenant B', null, 'IND-OTHER', null, null, $indicatorNow);
        foreach ([$indicatorA, $indicatorB, $indicatorC, $indicatorOtherTenant] as $customer) {
            $manager->persist($customer);
        }
        $manager->persist(CreditLimit::createActive($indicatorOrganization, $indicatorA, new MoneyAmount('100000.00'), new \DateTimeImmutable('2026-01-01'), null, 'Fixture controlada de indicadores A.', $indicatorOwner, $indicatorNow));
        $manager->persist(CreditLimit::createActive($indicatorOrganization, $indicatorB, new MoneyAmount('50000.00'), new \DateTimeImmutable('2026-01-01'), null, 'Fixture controlada de indicadores B.', $indicatorOwner, $indicatorNow));

        $indicatorReceivables = [
            Receivable::create($indicatorOrganization, $indicatorA, 'INDICATORS', 'A-UPCOMING', 'A-UPCOMING', new \DateTimeImmutable('2026-07-01'), new \DateTimeImmutable('2026-08-01'), new ReceivableAmount('40000.00'), $indicatorNow),
            Receivable::create($indicatorOrganization, $indicatorA, 'INDICATORS', 'A-OVERDUE', 'A-OVERDUE', new \DateTimeImmutable('2026-06-01'), new \DateTimeImmutable('2026-07-05'), new ReceivableAmount('10000.00'), $indicatorNow),
            Receivable::create($indicatorOrganization, $indicatorB, 'INDICATORS', 'B-UPCOMING', 'B-UPCOMING', new \DateTimeImmutable('2026-07-01'), new \DateTimeImmutable('2026-08-01'), new ReceivableAmount('60000.00'), $indicatorNow),
            Receivable::create($indicatorOrganization, $indicatorC, 'INDICATORS', 'C-UPCOMING', 'C-UPCOMING', new \DateTimeImmutable('2026-07-01'), new \DateTimeImmutable('2026-08-01'), new ReceivableAmount('20000.00'), $indicatorNow),
            Receivable::create($indicatorOtherOrganization, $indicatorOtherTenant, 'INDICATORS', 'OTHER-TENANT', 'OTHER-TENANT', new \DateTimeImmutable('2026-07-01'), new \DateTimeImmutable('2026-08-01'), new ReceivableAmount('999000.00'), $indicatorNow),
        ];
        for ($index = 1; $index <= 5; ++$index) {
            $paid = Receivable::create($indicatorOrganization, $indicatorA, 'INDICATORS', 'A-PAID-'.$index, 'A-PAID-'.$index, new \DateTimeImmutable('2026-05-01'), new \DateTimeImmutable('2026-06-01'), new ReceivableAmount('100.00'), $indicatorNow);
            $paymentDate = 5 === $index ? new \DateTimeImmutable('2026-06-06') : new \DateTimeImmutable('2026-06-01');
            $payment = $paid->registerPayment(new ReceivableAmount('100.00'), $paymentDate, $indicatorOwner, $indicatorNow);
            $indicatorReceivables[] = $paid;
            $manager->persist($payment);
        }
        foreach ($indicatorReceivables as $receivable) {
            $manager->persist($receivable);
        }

        $manager->flush();
    }
}
