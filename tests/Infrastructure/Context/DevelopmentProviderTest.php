<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Context;

use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Identity\Infrastructure\Context\DevelopmentCurrentUserProvider;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Repository\OrganizationRepository;
use App\Organizations\Infrastructure\Context\DevelopmentCurrentOrganizationProvider;
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
}
