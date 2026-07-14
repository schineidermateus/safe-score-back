<?php

declare(strict_types=1);

namespace App\Identity\Application\UseCase;

use App\Identity\Application\DTO\CreateUserInput;
use App\Identity\Application\DTO\UserOutput;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Domain\Exception\DomainException;

final readonly class CreateUser
{
    public function __construct(private UserRepository $users)
    {
    }

    public function execute(CreateUserInput $input): UserOutput
    {
        if (null !== $this->users->findByEmail($input->email)) {
            throw new DomainException('USER_EMAIL_ALREADY_EXISTS', 'Já existe um usuário com este e-mail.', 409, 'email');
        }

        $user = User::create($input->name, $input->email, new \DateTimeImmutable());
        $this->users->save($user);

        return UserOutput::fromEntity($user);
    }
}
