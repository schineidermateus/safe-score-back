<?php

declare(strict_types=1);

namespace App\Customers\Application\UseCase;

use App\Customers\Domain\ValueObject\DocumentNumber;
use App\Shared\Domain\Exception\DomainException;

final class CustomerDocument
{
    public static function normalize(?string $document): ?string
    {
        if (null === $document || '' === trim($document)) {
            return null;
        }

        try {
            return (string) new DocumentNumber($document);
        } catch (\InvalidArgumentException) {
            throw new DomainException('INVALID_DOCUMENT_NUMBER', 'O documento informado não é um CPF ou CNPJ válido.', 422, 'document');
        }
    }
}
