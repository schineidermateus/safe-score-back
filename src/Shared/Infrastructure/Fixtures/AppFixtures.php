<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixtures;

use App\Authorization\Domain\AuthorizationAction;
use App\Authorization\Domain\Entity\Capability;
use App\Authorization\Domain\Entity\Role;
use App\Identity\Domain\Entity\User;
use App\Industrial\Domain\Entity\BusinessPartner;
use App\Industrial\Domain\Entity\Machine;
use App\Industrial\Domain\Entity\Material;
use App\Industrial\Domain\Entity\Quarry;
use App\Industrial\Domain\Entity\StorageLocation;
use App\Industrial\Domain\Enum\BusinessPartnerType;
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
        $organizationA = Organization::create('Rochas Demo LTDA', 'Rochas Demo', '04252011000110', $now);
        $organizationB = Organization::create('Pedras Tenant B LTDA', 'Pedras B', '11222333000181', $now);
        $ownerA = User::create('Owner Demo', 'owner@stone.local', $now);
        $ownerB = User::create('Owner Tenant B', 'owner-b@stone.local', $now);
        $ownerA->linkExternalIdentity('https://auth.stone.local', 'fixture:owner-a', $now);
        $ownerB->linkExternalIdentity('https://auth.stone.local', 'fixture:owner-b', $now);

        $ownerProfile = Role::create('FOUNDATION_OWNER', 'Foundation owner');
        foreach (AuthorizationAction::cases() as $action) {
            $capability = $manager->getRepository(Capability::class)->findOneBy(['code' => $action->value])
                ?? Capability::create($action->value, str_replace('_', ' ', $action->value));
            $ownerProfile->grant($capability);
            $manager->persist($capability);
        }

        $membershipA = OrganizationMembership::join($organizationA, $ownerA, MembershipRole::Owner, $now);
        $membershipB = OrganizationMembership::join($organizationB, $ownerB, MembershipRole::Owner, $now);
        $membershipA->assignAuthorizationRole($ownerProfile);
        $membershipB->assignAuthorizationRole($ownerProfile);

        foreach ([$organizationA, $organizationB, $ownerA, $ownerB, $ownerProfile, $membershipA, $membershipB] as $entity) {
            $manager->persist($entity);
        }

        foreach ([
            BusinessPartner::create($organizationA, 'SUP-001', 'Fornecedor Demo LTDA', 'Fornecedor Demo', BusinessPartnerType::Supplier, $now),
            Material::create($organizationA, 'BRANCO-001', 'Granito Branco Demo', $now),
            Quarry::create($organizationA, 'PED-001', 'Pedreira Demo', $now),
            StorageLocation::create($organizationA, 'PATIO-A', 'Pátio principal', $now),
            Machine::create($organizationA, 'TEAR-01', 'Tear multifio 01', $now),
        ] as $entity) {
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
