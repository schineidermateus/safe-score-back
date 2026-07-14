<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixtures;

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

        $manager->flush();
    }
}
