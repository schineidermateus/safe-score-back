<?php

declare(strict_types=1);

namespace App\Tests\Identity\Support;

use App\Identity\Domain\Entity\ExternalIdentity;
use App\Identity\Domain\Repository\ExternalIdentityRepository;
use App\Tests\Support\EntityId;

final class InMemoryExternalIdentityRepository implements ExternalIdentityRepository
{
    /** @var array<int, ExternalIdentity> */
    private array $identities = [];
    private int $nextId = 1;

    public function save(ExternalIdentity $identity): void
    {
        if (null === $identity->id()) {
            EntityId::assign($identity, $this->nextId++);
        }
        $this->identities[$identity->requireId()] = $identity;
    }

    public function findActive(string $issuer, string $subject): ?ExternalIdentity
    {
        foreach ($this->identities as $identity) {
            if ($identity->issuer() === $issuer && $identity->subject() === $subject && $identity->isActive()) {
                return $identity;
            }
        }

        return null;
    }
}
