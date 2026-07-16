<?php

declare(strict_types=1);

namespace App\Identity\Application\Context;

interface AuthenticatedTokenProviderInterface
{
    /**
     * @throws \App\Shared\Domain\Exception\DomainException quando a request não está autenticada
     */
    public function current(): AuthenticatedToken;
}
