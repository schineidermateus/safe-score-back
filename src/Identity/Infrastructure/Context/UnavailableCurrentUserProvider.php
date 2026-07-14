<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Context;

use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Identity\Domain\Entity\User;
use App\Shared\Domain\Exception\DomainException;

final class UnavailableCurrentUserProvider implements CurrentUserProviderInterface
{
    public function currentUser(): User
    {
        throw new DomainException('UNAUTHENTICATED', 'Autenticação necessária.', 401);
    }
}
