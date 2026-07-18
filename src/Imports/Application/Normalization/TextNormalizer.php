<?php

declare(strict_types=1);

namespace App\Imports\Application\Normalization;

final class TextNormalizer
{
    public function optional(mixed $value, int $maxLength, string $field): ?string
    {
        if (!is_string($value) && null !== $value) {
            throw new \InvalidArgumentException(sprintf('%s deve ser texto.', $field));
        }
        $value = trim((string) $value);
        if ('' === $value) {
            return null;
        }
        if (mb_strlen($value) > $maxLength) {
            throw new \InvalidArgumentException(sprintf('%s excede %d caracteres.', $field, $maxLength));
        }
        if (1 === preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value)) {
            throw new \InvalidArgumentException(sprintf('%s contém caracteres de controle.', $field));
        }

        return $value;
    }

    public function required(mixed $value, int $maxLength, string $field): string
    {
        return $this->optional($value, $maxLength, $field) ?? throw new \InvalidArgumentException(sprintf('%s é obrigatório.', $field));
    }
}
