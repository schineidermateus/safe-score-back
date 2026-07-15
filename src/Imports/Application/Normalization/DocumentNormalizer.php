<?php

declare(strict_types=1);

namespace App\Imports\Application\Normalization;

use App\Customers\Application\UseCase\CustomerDocument;

final class DocumentNormalizer
{
    public function optional(mixed $value): ?string
    {
        return CustomerDocument::normalize(null === $value ? null : (string) $value);
    }

    public function required(mixed $value): string
    {
        return $this->optional($value) ?? throw new \InvalidArgumentException('Documento é obrigatório.');
    }
}
