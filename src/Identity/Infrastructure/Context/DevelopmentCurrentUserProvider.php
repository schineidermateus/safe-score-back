<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Context;

use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Domain\Exception\DomainException;

final readonly class DevelopmentCurrentUserProvider implements CurrentUserProviderInterface
{
    public function __construct(
        private UserRepository $users,
        private int $userId,
        string $environment,
    ) {
        if (!in_array($environment, ['dev', 'test'], true)) {
            throw new \LogicException('Development user provider cannot run outside dev or test.');
        }
    }

    public function currentUser(): User
    {
        return $this->users->findById($this->userId)
            ?? throw new DomainException('CURRENT_USER_NOT_FOUND', 'Usuário de desenvolvimento não encontrado.', 401);
    }
}
