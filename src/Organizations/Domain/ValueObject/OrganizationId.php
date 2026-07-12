<?php

declare(strict_types=1);

namespace App\Organizations\Domain\ValueObject;

final readonly class OrganizationId implements \Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if ('' === $normalized || strlen($normalized) > 64) {
            throw new \InvalidArgumentException('Invalid organization identifier.');
        }

        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
