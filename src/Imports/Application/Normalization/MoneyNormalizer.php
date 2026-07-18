<?php

declare(strict_types=1);

namespace App\Imports\Application\Normalization;

final class MoneyNormalizer
{
    public function normalize(mixed $value, string $field): string
    {
        $value = str_replace(["\u{00A0}", ' '], '', trim((string) $value));
        $value = preg_replace('/^R\$/iu', '', $value) ?? $value;
        if (1 === preg_match('/^\d{1,3}(?:\.\d{3})*,\d{1,2}$/', $value)) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (1 === preg_match('/^\d+,\d{1,2}$/', $value)) {
            $value = str_replace(',', '.', $value);
        } elseif (1 !== preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new \InvalidArgumentException(sprintf('%s possui formato monetário inválido ou ambíguo.', $field));
        }
        [$integer, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $integer = ltrim($integer, '0');
        $integer = '' === $integer ? '0' : $integer;
        if (strlen($integer) > 17) {
            throw new \InvalidArgumentException(sprintf('%s excede a precisão permitida.', $field));
        }

        return $integer.'.'.str_pad($fraction, 2, '0');
    }
}
