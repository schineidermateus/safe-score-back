<?php

declare(strict_types=1);

namespace App\Tests\Identity\Domain\Entity;

use App\Identity\Domain\Entity\ExternalIdentity;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Enum\UserStatus;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testItNormalizesEmailAndStartsWithoutDatabaseId(): void
    {
        $user = User::create('User', ' USER@Example.COM ', new \DateTimeImmutable());

        self::assertNull($user->id());
        self::assertSame('user@example.com', $user->email());
        self::assertSame(UserStatus::Active, $user->status());
    }

    public function testSuspendedAndInactiveUsersCannotAuthenticate(): void
    {
        $now = new \DateTimeImmutable();
        $suspended = User::create('Suspended', 'suspended@example.com', $now);
        $suspended->suspend($now);
        $inactive = User::create('Inactive', 'inactive@example.com', $now);
        $inactive->deactivate($now);

        self::assertFalse($suspended->isActive());
        self::assertFalse($inactive->isActive());
    }

    public function testItCanBeLinkedToAStableExternalIdentity(): void
    {
        $now = new \DateTimeImmutable('2026-07-16 12:00:00');
        $user = User::create('User', 'user@example.com', $now);

        $identity = ExternalIdentity::link($user, 'https://auth.stone.local', 'user:123', $now);

        self::assertSame('https://auth.stone.local', $identity->issuer());
        self::assertSame('user:123', $identity->subject());
        self::assertSame($user, $identity->user());
        self::assertTrue($identity->isActive());

        $identity->suspend($now);
        self::assertFalse($identity->isActive());
    }
}
