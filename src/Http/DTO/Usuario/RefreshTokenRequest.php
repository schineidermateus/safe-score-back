<?php

namespace App\Http\DTO\Usuario;

use App\Shared\Interface\RequestDTOInterface;
use Symfony\Component\Validator\Constraints as Assert;

readonly class RefreshTokenRequest implements RequestDTOInterface
{
    #[Assert\NotBlank]
    public string $refreshToken;

    private function __construct(string $refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['refreshToken']
        );
    }
}
