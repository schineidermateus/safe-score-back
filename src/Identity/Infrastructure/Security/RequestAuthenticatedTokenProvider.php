<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\Context\AuthenticatedToken;
use App\Identity\Application\Context\AuthenticatedTokenProviderInterface;
use App\Shared\Domain\Exception\DomainException;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Guarda, no escopo da request, os claims do token validado.
 *
 * O access-token handler o preenche na etapa do firewall; os providers de
 * contexto atual o leem durante a execução do controller. Cada request recebe
 * uma instância nova do container em produção (php-fpm/mod_php), então o estado
 * mutável nunca vaza entre requests.
 */
final class RequestAuthenticatedTokenProvider implements AuthenticatedTokenProviderInterface, ResetInterface
{
    private ?AuthenticatedToken $token = null;

    public function store(AuthenticatedToken $token): void
    {
        $this->token = $token;
    }

    public function current(): AuthenticatedToken
    {
        return $this->token
            ?? throw new DomainException('UNAUTHENTICATED', 'Autenticação necessária.', 401);
    }

    public function reset(): void
    {
        $this->token = null;
    }
}
