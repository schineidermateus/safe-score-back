<?php

declare(strict_types=1);

namespace App\Identity\Application\Context;

use App\Shared\Domain\Exception\DomainException;

/**
 * Retrato imutável dos claims de identidade carregados por uma request autenticada.
 *
 * É produzido pela camada JWT e consumido pelos providers de contexto atual,
 * de modo que a aplicação nunca lê o token de segurança bruto diretamente.
 */
final readonly class AuthenticatedToken
{
    /**
     * @param list<string>         $roles
     * @param array<string, mixed> $claims
     */
    public function __construct(
        public string $email,
        public ?int $organizationId,
        public ?string $subject = null,
        public array $roles = [],
        public array $claims = [],
    ) {
    }

    public function requireOrganizationId(): int
    {
        return $this->organizationId
            ?? throw new DomainException('ORGANIZATION_CONTEXT_REQUIRED', 'O token não identifica uma organização.', 403);
    }
}
