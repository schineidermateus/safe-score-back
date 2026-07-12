<?php

namespace App\Http\DTO\Usuario;

use App\Shared\Interface\RequestDTOInterface;
use Symfony\Component\Validator\Constraints as Assert;

readonly class LoginRequest implements RequestDTOInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    public string $senha;

    private function __construct(string $email, string $senha)
    {
        $this->email = $email;
        $this->senha = $senha;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['email'],
            $data['senha']
        );
    }
}
