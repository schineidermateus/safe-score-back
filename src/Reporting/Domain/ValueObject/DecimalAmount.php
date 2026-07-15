<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ValueObject;

final readonly class DecimalAmount implements \Stringable
{
    /** @var numeric-string */
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        if (1 !== preg_match('/^-?(?:0|[1-9]\d{0,29})(?:\.\d{1,2})?$/', $value)) {
            throw new \InvalidArgumentException('Amount must be a signed decimal string with at most two fractional digits.');
        }

        $canonical = bcadd(self::numeric($value), '0', 2);
        $this->value = '-0.00' === $canonical ? '0.00' : $canonical;
    }

    public static function zero(): self
    {
        return new self('0.00');
    }

    public function add(self $other): self
    {
        return new self(bcadd($this->value, $other->value, 2));
    }

    public function subtract(self $other): self
    {
        return new self(bcsub($this->value, $other->value, 2));
    }

    public function compare(self $other): int
    {
        return bccomp($this->value, $other->value, 2);
    }

    public function isZero(): bool
    {
        return 0 === $this->compare(self::zero());
    }

    public function isPositive(): bool
    {
        return $this->compare(self::zero()) > 0;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /** @return numeric-string */
    private static function numeric(string $value): string
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException('Amount must be numeric.');
        }

        return $value;
    }
}
