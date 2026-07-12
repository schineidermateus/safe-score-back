<?php

namespace App\Infrastructure\Security;

use App\Domain\Usuario\Entity\Usuario;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Contracts\Service\Attribute\Required;

trait LoggedInUserTrait
{
    protected Security $security;

    #[Required]
    public function setSecurity(Security $security): void
    {
        $this->security = $security;
    }

    /**
     * Retorna o usuário logado da request atual.
     *
     * @return Usuario|null
     * @throws AuthenticationCredentialsNotFoundException se não houver usuário logado
     */
    protected function getLoggedInUser(): ?Usuario
    {
        if (!isset($this->security)) {
            throw new \LogicException('Security service not set. Use setSecurity() before calling getLoggedInUser().');
        }

        $user = $this->security->getUser();

        if ($user instanceof Usuario) {
            return $user;
        }

        return null;
    }

    /**
     * Retorna o usuário logado obrigatoriamente, lança exception se não houver.
     *
     * @return Usuario
     * @throws AuthenticationCredentialsNotFoundException
     */
    protected function requireLoggedInUser(): Usuario
    {
        $user = $this->getLoggedInUser();

        if (!$user) {
            throw new AuthenticationCredentialsNotFoundException('Usuário não autenticado.');
        }

        return $user;
    }
}
