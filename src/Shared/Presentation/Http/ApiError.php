<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

final readonly class ApiError
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $field = null,
    ) {
    }

    /**
     * @return array{code: string, message: string, field?: string}
     */
    public function toArray(): array
    {
        $error = [
            'code' => $this->code,
            'message' => $this->message,
        ];

        if (null !== $this->field) {
            $error['field'] = $this->field;
        }

        return $error;
    }
}
