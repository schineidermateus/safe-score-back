<?php

namespace App\Http\DTO\Usuario;

use App\Shared\Interface\RequestDTOInterface;

class EmailTokenRequest implements RequestDTOInterface
{

    #[Assert\NotBlank]
    public string $token;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    private function __construct(string $token, string $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['token'],
            $data['email'],
        );
    }
}
