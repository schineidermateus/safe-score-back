<?php

declare(strict_types=1);

namespace App\Customers\Domain\ValueObject;

final readonly class DocumentNumber implements \Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        $value = preg_replace('/\D/', '', $value) ?? '';

        if (!$this->isValidCpf($value) && !$this->isValidCnpj($value)) {
            throw new \InvalidArgumentException('Invalid CPF or CNPJ.');
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function isValidCpf(string $value): bool
    {
        if (11 !== strlen($value) || 1 === count(array_unique(str_split($value)))) {
            return false;
        }

        for ($digit = 9; $digit < 11; ++$digit) {
            $sum = 0;
            for ($index = 0; $index < $digit; ++$index) {
                $sum += ((int) $value[$index]) * (($digit + 1) - $index);
            }

            $check = (10 * $sum) % 11;
            if (10 === $check) {
                $check = 0;
            }

            if ($check !== (int) $value[$digit]) {
                return false;
            }
        }

        return true;
    }

    private function isValidCnpj(string $value): bool
    {
        if (14 !== strlen($value) || 1 === count(array_unique(str_split($value)))) {
            return false;
        }

        $weights = [
            [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
            [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        ];

        foreach ($weights as $offset => $weight) {
            $sum = 0;
            foreach ($weight as $index => $multiplier) {
                $sum += ((int) $value[$index]) * $multiplier;
            }

            $remainder = $sum % 11;
            $check = $remainder < 2 ? 0 : 11 - $remainder;

            if ($check !== (int) $value[12 + $offset]) {
                return false;
            }
        }

        return true;
    }
}
