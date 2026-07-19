<?php

declare(strict_types=1);

namespace App\Tests\Authorization\Application;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Authorization\Domain\Entity\Capability;
use App\Authorization\Domain\Entity\Role;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Shared\Domain\Exception\DomainException;
use App\Tests\Support\CurrentContextStub;
use PHPUnit\Framework\TestCase;

final class AuthorizationServiceTest extends TestCase
{
    public function testCapabilityGrantIsIndependentFromAdministrativeRoleName(): void
    {
        $context = $this->context(MembershipRole::Viewer, AuthorizationAction::MaterialWrite);
        (new AuthorizationService($context))->assertGranted(AuthorizationAction::MaterialWrite);
        self::addToAssertionCount(1);
    }

    public function testMissingCapabilityIsDeniedEvenForOwner(): void
    {
        $context = $this->context(MembershipRole::Owner);
        $this->expectException(DomainException::class);
        (new AuthorizationService($context))->assertGranted(AuthorizationAction::MaterialRead);
    }

    public function testManageMembersCannotAssignOwnerWithoutSpecificCapability(): void
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization', null, null, $now);
        $actor = User::create('Actor', 'actor@example.com', $now);
        $targetUser = User::create('Target', 'target@example.com', $now);
        $actorMembership = OrganizationMembership::join($organization, $actor, MembershipRole::Viewer, $now);
        $targetMembership = OrganizationMembership::join($organization, $targetUser, MembershipRole::Viewer, $now);
        $profile = Role::create('MEMBER_MANAGER', 'Member manager');
        $profile->grant(Capability::create(AuthorizationAction::ManageMembers->value, 'Manage members'));
        $actorMembership->assignAuthorizationRole($profile);
        $authorization = new AuthorizationService(new CurrentContextStub($actor, $organization, $actorMembership));

        $this->expectException(DomainException::class);
        $authorization->assertCanManageMembership($targetMembership, MembershipRole::Owner);
    }

    private function context(MembershipRole $administrativeRole, ?AuthorizationAction $grant = null): CurrentContextStub
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization', null, null, $now);
        $user = User::create('User', 'user@example.com', $now);
        $membership = OrganizationMembership::join($organization, $user, $administrativeRole, $now);
        if (null !== $grant) {
            $role = Role::create('TEST_PROFILE', 'Test profile');
            $role->grant(Capability::create($grant->value, 'Test capability'));
            $membership->assignAuthorizationRole($role);
        }

        return new CurrentContextStub($user, $organization, $membership);
    }
}
