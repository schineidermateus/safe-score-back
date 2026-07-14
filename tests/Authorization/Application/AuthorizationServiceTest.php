<?php

declare(strict_types=1);

namespace App\Tests\Authorization\Application;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Shared\Domain\Exception\DomainException;
use App\Tests\Support\CurrentContextStub;
use App\Tests\Support\EntityId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthorizationServiceTest extends TestCase
{
    #[DataProvider('allowedActions')]
    public function testRoleMatrixAllowsExpectedActions(MembershipRole $role, AuthorizationAction $action): void
    {
        $service = new AuthorizationService($this->context($role));
        $service->assertGranted($action);

        self::addToAssertionCount(1);
    }

    /** @return iterable<string, array{MembershipRole, AuthorizationAction}> */
    public static function allowedActions(): iterable
    {
        yield 'owner all' => [MembershipRole::Owner, AuthorizationAction::AssignOwner];
        yield 'admin manages members' => [MembershipRole::Admin, AuthorizationAction::ManageMembers];
        yield 'analyst manages customers' => [MembershipRole::Analyst, AuthorizationAction::ManageCustomers];
        yield 'viewer reads' => [MembershipRole::Viewer, AuthorizationAction::ViewData];
    }

    #[DataProvider('deniedActions')]
    public function testRoleMatrixDeniesExpectedActions(MembershipRole $role, AuthorizationAction $action): void
    {
        $this->expectException(DomainException::class);
        (new AuthorizationService($this->context($role)))->assertGranted($action);
    }

    /** @return iterable<string, array{MembershipRole, AuthorizationAction}> */
    public static function deniedActions(): iterable
    {
        yield 'admin cannot assign owner' => [MembershipRole::Admin, AuthorizationAction::AssignOwner];
        yield 'analyst cannot manage members' => [MembershipRole::Analyst, AuthorizationAction::ManageMembers];
        yield 'viewer cannot alter customer' => [MembershipRole::Viewer, AuthorizationAction::ManageCustomers];
    }

    private function context(MembershipRole $role): CurrentContextStub
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization', null, null, $now);
        $user = User::create('User', $role->value.'@example.com', $now);
        EntityId::assign($organization, 1);
        EntityId::assign($user, 1);
        $membership = OrganizationMembership::join($organization, $user, $role, $now);
        EntityId::assign($membership, 1);

        return new CurrentContextStub($user, $organization, $membership);
    }
}
