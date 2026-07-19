<?php

declare(strict_types=1);

namespace App\Imports\Application\Normalization;

final class DocumentNormalizer
{
    public function optional(mixed $value): ?string
    {
        if (null === $value || '' === trim((string) $value)) {
            return null;
        }

        $normalized = preg_replace('/\D/', '', (string) $value) ?? '';

        return '' === $normalized ? null : $normalized;
    }

    public function required(mixed $value): string
    {
        return $this->optional($value) ?? throw new \InvalidArgumentException('Documento é obrigatório.');
    }
}
