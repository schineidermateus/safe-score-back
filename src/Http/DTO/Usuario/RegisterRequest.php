<?php

namespace App\Http\DTO\Usuario;

use App\Shared\Interface\RequestDTOInterface;
use Symfony\Component\Validator\Constraints as Assert;

readonly class RegisterRequest implements RequestDTOInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    public string $nome;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public string $senha;

    public function __construct(string $email, string $nome, string $senha)
    {
        $this->email = $email;
        $this->nome = $nome;
        $this->senha = $senha;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['email'],
            $data['nome'],
            $data['senha'],
        );
    }
}
