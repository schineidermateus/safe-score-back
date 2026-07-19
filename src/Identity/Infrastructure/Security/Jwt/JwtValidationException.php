<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security\Jwt;

/**
 * Lançada sempre que um access token não é confiável: malformado, assinatura
 * inválida, expirado, issuer/audience incorretos ou chave de assinatura não resolvida.
 */
class JwtValidationException extends \RuntimeException
{
}
