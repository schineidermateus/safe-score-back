<?php

declare(strict_types=1);

namespace App\Tests\Identity\Support;

use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Tests\Support\EntityId;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<int, User> */
    private array $users = [];

    private int $nextId = 1;

    public function save(User $user): void
    {
        if (null === $user->id()) {
            EntityId::assign($user, $this->nextId++);
        }

        $this->users[$user->requireId()] = $user;
    }

    public function findById(int $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    public function findByEmail(string $email): ?User
    {
        $email = mb_strtolower(trim($email));
        foreach ($this->users as $user) {
            if ($user->email() === $email) {
                return $user;
            }
        }

        return null;
    }
}
