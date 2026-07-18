<?php

declare(strict_types=1);

namespace App\Imports\Application\Normalization;

final class DateNormalizer
{
    public function optional(mixed $value, string $field): ?\DateTimeImmutable
    {
        $value = trim((string) $value);
        if ('' === $value) {
            return null;
        }
        foreach (['!Y-m-d', '!d/m/Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            $errors = \DateTimeImmutable::getLastErrors();
            if (false !== $date && (false === $errors || (0 === $errors['warning_count'] && 0 === $errors['error_count'])) && $date->format(substr($format, 1)) === $value) {
                return $date;
            }
        }
        throw new \InvalidArgumentException(sprintf('%s deve usar YYYY-MM-DD ou DD/MM/YYYY.', $field));
    }

    public function required(mixed $value, string $field): \DateTimeImmutable
    {
        return $this->optional($value, $field) ?? throw new \InvalidArgumentException(sprintf('%s é obrigatório.', $field));
    }
}
