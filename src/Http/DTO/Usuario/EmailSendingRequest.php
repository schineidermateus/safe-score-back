<?php

namespace App\Http\DTO\Usuario;

use App\Shared\Interface\RequestDTOInterface;

class EmailSendingRequest implements RequestDTOInterface
{

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    private function __construct(string $email)
    {
        $this->email = $email;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['email'],
        );
    }
}
