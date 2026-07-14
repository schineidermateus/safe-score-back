<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

use App\Identity\Domain\Entity\User;

final readonly class UserOutput
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $status,
    ) {
    }

    public static function fromEntity(User $user): self
    {
        return new self($user->requireId(), $user->name(), $user->email(), $user->status()->value);
    }

    /** @return array{id: int, name: string, email: string, status: string} */
    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email, 'status' => $this->status];
    }
}
