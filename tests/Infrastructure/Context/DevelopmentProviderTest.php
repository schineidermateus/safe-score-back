<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Context;

use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Identity\Infrastructure\Context\DevelopmentCurrentUserProvider;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Organizations\Domain\Repository\OrganizationMembershipRepository;
use App\Organizations\Domain\Repository\OrganizationRepository;
use App\Organizations\Infrastructure\Context\DevelopmentCurrentMembershipProvider;
use App\Organizations\Infrastructure\Context\DevelopmentCurrentOrganizationProvider;
use App\Shared\Domain\Exception\DomainException;
use App\Tests\Support\EntityId;
use PHPUnit\Framework\TestCase;

final class DevelopmentProviderTest extends TestCase
{
    public function testDevelopmentProvidersResolveConfiguredEntities(): void
    {
        $now = new \DateTimeImmutable();
        $user = User::create('Dev', 'dev@example.com', $now);
        $organization = Organization::create('Dev Organization', null, null, $now);
        EntityId::assign($user, 10);
        EntityId::assign($organization, 20);

        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('findById')->with(10)->willReturn($user);
        $organizations = $this->createMock(OrganizationRepository::class);
        $organizations->expects(self::once())->method('findById')->with(20)->willReturn($organization);

        self::assertSame($user, (new DevelopmentCurrentUserProvider($users, 10, 'test'))->currentUser());
        self::assertSame(
            $organization,
            (new DevelopmentCurrentOrganizationProvider($organizations, 20, 'dev'))->currentOrganization(),
        );
    }

    public function testDevelopmentProviderCannotBeUsedInProduction(): void
    {
        $this->expectException(\LogicException::class);

        new DevelopmentCurrentUserProvider($this->createMock(UserRepository::class), 1, 'prod');
    }

    public function testEveryDevelopmentProviderRejectsProduction(): void
    {
        $blocked = 0;

        try {
            new DevelopmentCurrentOrganizationProvider($this->createMock(OrganizationRepository::class), 1, 'prod');
        } catch (\LogicException) {
            ++$blocked;
        }

        try {
            new DevelopmentCurrentMembershipProvider(
                $this->createMock(OrganizationMembershipRepository::class),
                $this->createMock(CurrentUserProviderInterface::class),
                $this->createMock(CurrentOrganizationProviderInterface::class),
                'prod',
            );
        } catch (\LogicException) {
            ++$blocked;
        }

        self::assertSame(2, $blocked);
    }

    public function testCurrentMembershipRequiresConfiguredUserToBelongToConfiguredOrganization(): void
    {
        $now = new \DateTimeImmutable();
        $user = User::create('Dev', 'dev@example.com', $now);
        $organization = Organization::create('Organization', null, null, $now);
        $users = $this->createMock(CurrentUserProviderInterface::class);
        $users->method('currentUser')->willReturn($user);
        $organizations = $this->createMock(CurrentOrganizationProviderInterface::class);
        $organizations->method('currentOrganization')->willReturn($organization);
        $memberships = $this->createMock(OrganizationMembershipRepository::class);
        $memberships->method('findByOrganizationAndUser')->with($organization, $user)->willReturn(null);

        $this->expectException(DomainException::class);
        (new DevelopmentCurrentMembershipProvider($memberships, $users, $organizations, 'dev'))->currentMembership();
    }

    public function testCurrentMembershipRejectsSuspendedMembership(): void
    {
        $now = new \DateTimeImmutable();
        $user = User::create('Dev', 'dev@example.com', $now);
        $organization = Organization::create('Organization', null, null, $now);
        $membership = OrganizationMembership::join($organization, $user, MembershipRole::Owner, $now);
        $membership->suspend($now);
        $users = $this->createMock(CurrentUserProviderInterface::class);
        $users->method('currentUser')->willReturn($user);
        $organizations = $this->createMock(CurrentOrganizationProviderInterface::class);
        $organizations->method('currentOrganization')->willReturn($organization);
        $memberships = $this->createMock(OrganizationMembershipRepository::class);
        $memberships->method('findByOrganizationAndUser')->willReturn($membership);

        $this->expectException(DomainException::class);
        (new DevelopmentCurrentMembershipProvider($memberships, $users, $organizations, 'test'))->currentMembership();
    }
}
