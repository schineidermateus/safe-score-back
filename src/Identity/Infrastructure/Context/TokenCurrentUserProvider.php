<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Context;

use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Identity\Domain\Entity\User;
use App\Shared\Domain\Exception\DomainException;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Provider de usuário atual para produção: devolve a entidade que o firewall
 * autenticou a partir do JWT (carregada pelo provider "safe_score_users" via
 * e-mail do token).
 */
final readonly class TokenCurrentUserProvider implements CurrentUserProviderInterface
{
    public function __construct(private Security $security)
    {
    }

    public function currentUser(): User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new DomainException('UNAUTHENTICATED', 'Autenticação necessária.', 401);
        }
        if (!$user->isActive()) {
            throw new DomainException('USER_INACTIVE', 'A conta do usuário não está ativa.', 403);
        }

        return $user;
    }
}
