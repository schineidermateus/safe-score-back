<?php

declare(strict_types=1);

namespace App\Tests\Identity\Domain\Entity;

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

    public function testItCanBeLinkedToAStableExternalIdentity(): void
    {
        $now = new \DateTimeImmutable('2026-07-16 12:00:00');
        $user = User::create('User', 'user@example.com', $now);

        $user->linkExternalIdentity('https://auth.safescore.local', 'user:123', $now);

        self::assertSame('https://auth.safescore.local', $user->identityIssuer());
        self::assertSame('user:123', $user->externalSubject());
    }
}
