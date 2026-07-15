<?php

declare(strict_types=1);

namespace App\Receivables\Domain\ValueObject;

final readonly class ReceivableAmount implements \Stringable
{
    /** @var numeric-string */
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        if (1 !== preg_match('/^(?:0|[1-9]\d{0,16})(?:\.\d{1,2})?$/', $value)) {
            throw new \InvalidArgumentException('Amount must be a non-negative decimal with up to 17 integer and 2 fractional digits.');
        }

        [$integer, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $canonical = $integer.'.'.str_pad($fraction, 2, '0');
        if (!is_numeric($canonical)) {
            throw new \LogicException('Validated amount did not produce a numeric decimal.');
        }
        $this->value = $canonical;
    }

    public function add(self $other): self
    {
        return new self(bcadd($this->value, $other->value, 2));
    }

    public function subtract(self $other): self
    {
        if ($this->compare($other) < 0) {
            throw new \InvalidArgumentException('Amount subtraction cannot produce a negative result.');
        }

        return new self(bcsub($this->value, $other->value, 2));
    }

    public function compare(self $other): int
    {
        return bccomp($this->value, $other->value, 2);
    }

    public function isZero(): bool
    {
        return '0.00' === $this->value;
    }

    public function isPositive(): bool
    {
        return !$this->isZero();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
