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
    #[DataProvider('completeMatrix')]
    public function testCompleteRoleMatrix(
        MembershipRole $role,
        AuthorizationAction $action,
        bool $expected,
    ): void {
        $granted = true;

        try {
            (new AuthorizationService($this->context($role)))->assertGranted($action);
        } catch (DomainException) {
            $granted = false;
        }

        self::assertSame($expected, $granted, $role->value.' / '.$action->value);
    }

    /** @return iterable<string, array{MembershipRole, AuthorizationAction, bool}> */
    public static function completeMatrix(): iterable
    {
        $operational = [
            AuthorizationAction::ViewData,
            AuthorizationAction::ManageCustomers,
            AuthorizationAction::ManageCredit,
            AuthorizationAction::ManageReceivables,
            AuthorizationAction::ImportData,
            AuthorizationAction::ResolveAlerts,
            AuthorizationAction::RecalculateScore,
        ];

        foreach (MembershipRole::cases() as $role) {
            foreach (AuthorizationAction::cases() as $action) {
                $allowed = match ($role) {
                    MembershipRole::Owner => true,
                    MembershipRole::Admin => in_array($action, [...$operational, AuthorizationAction::ManageMembers], true),
                    MembershipRole::Analyst => in_array($action, $operational, true),
                    MembershipRole::Viewer => AuthorizationAction::ViewData === $action,
                };

                yield $role->value.' '.$action->value => [$role, $action, $allowed];
            }
        }
    }

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
        yield 'owner recalculates score' => [MembershipRole::Owner, AuthorizationAction::RecalculateScore];
        yield 'admin recalculates score' => [MembershipRole::Admin, AuthorizationAction::RecalculateScore];
        yield 'analyst recalculates score' => [MembershipRole::Analyst, AuthorizationAction::RecalculateScore];
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
        yield 'viewer cannot recalculate score' => [MembershipRole::Viewer, AuthorizationAction::RecalculateScore];
    }

    public function testAdminCannotManageAnExistingOwner(): void
    {
        $service = new AuthorizationService($this->context(MembershipRole::Admin));
        $ownerContext = $this->context(MembershipRole::Owner);

        $this->expectException(DomainException::class);
        $service->assertCanManageMembership($ownerContext->currentMembership(), MembershipRole::Admin);
    }

    public function testSuspendedMembershipDoesNotAuthorizeAnOtherwiseAllowedAction(): void
    {
        $context = $this->context(MembershipRole::Owner);
        $context->currentMembership()->suspend(new \DateTimeImmutable());

        $this->expectException(DomainException::class);
        (new AuthorizationService($context))->assertGranted(AuthorizationAction::ViewData);
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
