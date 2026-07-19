<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Entity\ExternalIdentity;

interface ExternalIdentityRepository
{
    public function save(ExternalIdentity $identity): void;

    public function findActive(string $issuer, string $subject): ?ExternalIdentity;
}
