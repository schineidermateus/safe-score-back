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
}
