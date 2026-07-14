<?php

declare(strict_types=1);

namespace App\Organizations\Application\DTO;

use App\Organizations\Domain\Entity\OrganizationMembership;

final readonly class MembershipOutput
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $name,
        public string $email,
        public string $role,
        public string $status,
        public string $joinedAt,
    ) {
    }

    public static function fromEntity(OrganizationMembership $membership): self
    {
        return new self(
            $membership->requireId(),
            $membership->user()->requireId(),
            $membership->user()->name(),
            $membership->user()->email(),
            $membership->role()->value,
            $membership->status()->value,
            $membership->joinedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, int|string> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'joined_at' => $this->joinedAt,
        ];
    }
}
