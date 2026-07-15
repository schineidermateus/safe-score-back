<?php

declare(strict_types=1);

namespace App\Tests\Authorization\Application;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Organizations\Domain\Enum\MembershipStatus;
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
            AuthorizationAction::CreditLimitRead,
            AuthorizationAction::CreditLimitWrite,
            AuthorizationAction::ReceivableRead,
            AuthorizationAction::ReceivableWrite,
            AuthorizationAction::ReceivablePaymentRegister,
            AuthorizationAction::ReceivableCancel,
            AuthorizationAction::ImportData,
            AuthorizationAction::ResolveAlerts,
            AuthorizationAction::RecalculateScore,
        ];

        foreach (MembershipRole::cases() as $role) {
            foreach (AuthorizationAction::cases() as $action) {
                $allowed = match ($role) {
                    MembershipRole::Owner => true,
                    MembershipRole::Admin => in_array($action, [...$operational, AuthorizationAction::CreditLimitRevoke, AuthorizationAction::ManageMembers], true),
                    MembershipRole::Analyst => in_array($action, $operational, true),
                    MembershipRole::Viewer => in_array($action, [AuthorizationAction::ViewData, AuthorizationAction::CreditLimitRead, AuthorizationAction::ReceivableRead], true),
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
        yield 'viewer reads credit limits' => [MembershipRole::Viewer, AuthorizationAction::CreditLimitRead];
        yield 'viewer reads receivables' => [MembershipRole::Viewer, AuthorizationAction::ReceivableRead];
        yield 'analyst writes credit limits' => [MembershipRole::Analyst, AuthorizationAction::CreditLimitWrite];
        yield 'admin revokes credit limits' => [MembershipRole::Admin, AuthorizationAction::CreditLimitRevoke];
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
        yield 'viewer cannot write credit limits' => [MembershipRole::Viewer, AuthorizationAction::CreditLimitWrite];
        yield 'analyst cannot revoke credit limits' => [MembershipRole::Analyst, AuthorizationAction::CreditLimitRevoke];
        yield 'viewer cannot register payment' => [MembershipRole::Viewer, AuthorizationAction::ReceivablePaymentRegister];
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

    #[DataProvider('inactiveMembershipStatuses')]
    public function testEveryInactiveMembershipStatusDeniesAccess(MembershipStatus $status): void
    {
        $context = $this->context(MembershipRole::Owner);
        $property = new \ReflectionProperty(OrganizationMembership::class, 'status');
        $property->setValue($context->currentMembership(), $status);

        $this->expectException(DomainException::class);
        (new AuthorizationService($context))->assertGranted(AuthorizationAction::ViewData);
    }

    public function testInactiveMembershipDeniesEveryCreditLimitCapability(): void
    {
        $context = $this->context(MembershipRole::Owner);
        $context->currentMembership()->suspend(new \DateTimeImmutable());
        $service = new AuthorizationService($context);

        foreach ([
            AuthorizationAction::CreditLimitRead,
            AuthorizationAction::CreditLimitWrite,
            AuthorizationAction::CreditLimitRevoke,
        ] as $action) {
            try {
                $service->assertGranted($action);
                self::fail('Inactive membership must not grant '.$action->value.'.');
            } catch (DomainException $exception) {
                self::assertSame(403, $exception->statusCode());
            }
        }
    }

    public function testInactiveMembershipDeniesEveryReceivableCapability(): void
    {
        $context = $this->context(MembershipRole::Owner);
        $context->currentMembership()->suspend(new \DateTimeImmutable());
        $service = new AuthorizationService($context);

        foreach ([
            AuthorizationAction::ReceivableRead,
            AuthorizationAction::ReceivableWrite,
            AuthorizationAction::ReceivablePaymentRegister,
            AuthorizationAction::ReceivableCancel,
        ] as $action) {
            try {
                $service->assertGranted($action);
                self::fail('Inactive membership must not grant '.$action->value.'.');
            } catch (DomainException $exception) {
                self::assertSame(403, $exception->statusCode());
            }
        }
    }

    /** @return iterable<string, array{MembershipStatus}> */
    public static function inactiveMembershipStatuses(): iterable
    {
        yield MembershipStatus::Invited->value => [MembershipStatus::Invited];
        yield MembershipStatus::Suspended->value => [MembershipStatus::Suspended];
        yield MembershipStatus::Removed->value => [MembershipStatus::Removed];
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
