<?php

declare(strict_types=1);

namespace App\Identity\Application\Context;

/**
 * Retrato imutável dos claims de identidade carregados por uma request autenticada.
 *
 * É produzido pela camada JWT e consumido pelos providers de contexto atual,
 * de modo que a aplicação nunca lê o token de segurança bruto diretamente.
 */
final readonly class AuthenticatedToken
{
    public function __construct(
        public string $issuer,
        public string $subject,
        public ?string $email,
        public ?int $organizationId,
    ) {
    }
}
