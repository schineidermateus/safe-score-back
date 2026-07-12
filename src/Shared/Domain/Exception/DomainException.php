<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

class DomainException extends \RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly int $statusCode = 422,
        private readonly ?string $field = null,
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function field(): ?string
    {
        return $this->field;
    }
}
