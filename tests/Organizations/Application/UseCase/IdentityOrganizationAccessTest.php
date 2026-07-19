<?php

declare(strict_types=1);

namespace App\Tests\Organizations\Application\UseCase;

use App\Identity\Application\UseCase\GetCurrentUser;
use App\Identity\Domain\Entity\User;
use App\Organizations\Application\UseCase\ListAccessibleOrganizations;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Tests\Identity\Support\InMemoryUserRepository;
use App\Tests\Organizations\Support\InMemoryMembershipRepository;
use App\Tests\Organizations\Support\InMemoryOrganizationRepository;
use App\Tests\Support\CurrentContextStub;
use PHPUnit\Framework\TestCase;

final class IdentityOrganizationAccessTest extends TestCase
{
    public function testListAndSwitchUseOnlyActiveMembershipsOfCurrentUser(): void
    {
        $now = new \DateTimeImmutable();
        $users = new InMemoryUserRepository();
        $organizations = new InMemoryOrganizationRepository();
        $memberships = new InMemoryMembershipRepository();
        $user = User::create('User', 'user@example.com', $now);
        $other = User::create('Other', 'other@example.com', $now);
        $users->save($user);
        $users->save($other);
        $alpha = Organization::create('Alpha', null, null, $now);
        $beta = Organization::create('Beta', null, null, $now);
        $foreign = Organization::create('Foreign', null, null, $now);
        foreach ([$alpha, $beta, $foreign] as $organization) {
            $organizations->save($organization);
        }
        $current = OrganizationMembership::join($alpha, $user, MembershipRole::Owner, $now);
        $target = OrganizationMembership::join($beta, $user, MembershipRole::Viewer, $now);
        $foreignMembership = OrganizationMembership::join($foreign, $other, MembershipRole::Owner, $now);
        foreach ([$current, $target, $foreignMembership] as $membership) {
            $memberships->save($membership);
        }
        $context = new CurrentContextStub($user, $alpha, $current);

        $profile = (new GetCurrentUser($context))->execute();
        self::assertSame(1, $profile['user']['id']);
        self::assertSame(1, $profile['organization']['id']);
        self::assertArrayNotHasKey('password_hash', $profile['user']);

        $listed = (new ListAccessibleOrganizations($memberships, $context))->execute();
        self::assertSame(['Alpha', 'Beta'], array_column($listed, 'legal_name'));

        self::assertNull($memberships->findActiveByUserAndOrganizationId($user, $foreign->requireId()));
    }

    public function testSuspendedMembershipCannotBeListedOrSelected(): void
    {
        $now = new \DateTimeImmutable();
        $user = User::create('User', 'user@example.com', $now);
        $organization = Organization::create('Current', null, null, $now);
        $target = Organization::create('Suspended target', null, null, $now);
        $users = new InMemoryUserRepository();
        $organizations = new InMemoryOrganizationRepository();
        $memberships = new InMemoryMembershipRepository();
        $users->save($user);
        $organizations->save($organization);
        $organizations->save($target);
        $current = OrganizationMembership::join($organization, $user, MembershipRole::Owner, $now);
        $suspended = OrganizationMembership::join($target, $user, MembershipRole::Viewer, $now);
        $suspended->suspend($now);
        $memberships->save($current);
        $memberships->save($suspended);
        $context = new CurrentContextStub($user, $organization, $current);

        self::assertCount(1, (new ListAccessibleOrganizations($memberships, $context))->execute());

        self::assertNull($memberships->findActiveByUserAndOrganizationId($user, $target->requireId()));
    }
}
