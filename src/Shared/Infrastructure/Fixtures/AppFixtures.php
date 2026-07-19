<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixtures;

use App\Authorization\Domain\AuthorizationAction;
use App\Authorization\Domain\Entity\Capability;
use App\Authorization\Domain\Entity\Role;
use App\Identity\Domain\Entity\ExternalIdentity;
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
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $organizationA = Organization::create('Rochas Demo LTDA', 'Rochas Demo', '04252011000110', $now);
        $organizationB = Organization::create('Pedras Tenant B LTDA', 'Pedras B', '11222333000181', $now);
        $organizationUnavailable = Organization::create('Tenant Indisponível LTDA', 'Tenant Indisponível', '99888777000166', $now);
        $organizationUnavailable->suspend($now);
        $multiTenantUser = User::create('Usuário Multi Tenant', 'multi@stone.local', $now);
        $ownerB = User::create('Owner Tenant B', 'owner-b@stone.local', $now);
        $suspendedMembershipUser = User::create('Membership Suspensa', 'membership-suspended@stone.local', $now);
        $blockedUser = User::create('Usuário Bloqueado', 'blocked@stone.local', $now);
        $blockedUser->suspend($now);
        $userWithoutMembership = User::create('Sem Organização', 'no-membership@stone.local', $now);
        $identities = [];
        foreach ([$multiTenantUser, $ownerB, $suspendedMembershipUser, $blockedUser, $userWithoutMembership] as $index => $user) {
            $identities[] = ExternalIdentity::link($user, 'https://auth.stone.local', 'fixture:user-'.($index + 1), $now);
        }

        $ownerProfile = Role::create('FOUNDATION_OWNER', 'Foundation owner');
        foreach (AuthorizationAction::cases() as $action) {
            $capability = $manager->getRepository(Capability::class)->findOneBy(['code' => $action->value])
                ?? Capability::create($action->value, str_replace('_', ' ', $action->value));
            $ownerProfile->grant($capability);
            $manager->persist($capability);
        }

        $membershipA = OrganizationMembership::join($organizationA, $multiTenantUser, MembershipRole::Owner, $now);
        $membershipMultiTenantB = OrganizationMembership::join($organizationB, $multiTenantUser, MembershipRole::Viewer, $now);
        $membershipB = OrganizationMembership::join($organizationB, $ownerB, MembershipRole::Owner, $now);
        $suspendedMembership = OrganizationMembership::join($organizationB, $suspendedMembershipUser, MembershipRole::Viewer, $now);
        $suspendedMembership->suspend($now);
        $blockedMembership = OrganizationMembership::join($organizationA, $blockedUser, MembershipRole::Viewer, $now);
        $membershipA->assignAuthorizationRole($ownerProfile);
        $membershipMultiTenantB->assignAuthorizationRole($ownerProfile);
        $membershipB->assignAuthorizationRole($ownerProfile);

        foreach ([
            $organizationA,
            $organizationB,
            $organizationUnavailable,
            $multiTenantUser,
            $ownerB,
            $suspendedMembershipUser,
            $blockedUser,
            $userWithoutMembership,
            $ownerProfile,
            ...$identities,
            $membershipA,
            $membershipMultiTenantB,
            $membershipB,
            $suspendedMembership,
            $blockedMembership,
        ] as $entity) {
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
