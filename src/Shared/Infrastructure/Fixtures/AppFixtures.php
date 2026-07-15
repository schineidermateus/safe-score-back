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

        foreach ([
            OrganizationMembership::join($organizationA, $owner, MembershipRole::Owner, $now),
            OrganizationMembership::join($organizationA, $admin, MembershipRole::Admin, $now),
            OrganizationMembership::join($organizationA, $analyst, MembershipRole::Analyst, $now),
            OrganizationMembership::join($organizationA, $viewer, MembershipRole::Viewer, $now),
            OrganizationMembership::join($organizationB, $secondOwner, MembershipRole::Owner, $now),
            Customer::create($organizationA, 'Cliente Organização A', 'Cliente A', '04252011000110', 'A-001', null, null, $now),
            Customer::create($organizationB, 'Cliente Organização B', 'Cliente B', '04252011000110', 'B-001', null, null, $now),
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

        $manager->flush();
    }
}
