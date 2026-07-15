<?php

declare(strict_types=1);

namespace App\Credit\Domain\ValueObject;

final readonly class MoneyAmount implements \Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        if (1 !== preg_match('/^(?:0|[1-9]\d{0,16})(?:\.\d{1,2})?$/', $value)) {
            throw new \InvalidArgumentException('Amount must be a decimal string with up to 17 integer and 2 fractional digits.');
        }

        [$integer, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $fraction = str_pad($fraction, 2, '0');
        if ('0' === $integer && '00' === $fraction) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        $this->value = $integer.'.'.$fraction;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
