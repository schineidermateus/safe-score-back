<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Domain\Exception\DomainException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<User> */
final class DoctrineUserRepository extends ServiceEntityRepository implements UserRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);

        try {
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException $exception) {
            if (str_contains($exception->getMessage(), 'uniq_user_external_identity')) {
                throw new DomainException('USER_EXTERNAL_IDENTITY_ALREADY_LINKED', 'A identidade externa já está vinculada a outro usuário.', 409);
            }

            throw new DomainException('USER_EMAIL_ALREADY_EXISTS', 'Já existe um usuário com este e-mail.', 409, 'email');
        }
    }

    public function findById(int $id): ?User
    {
        return $this->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => mb_strtolower(trim($email))]);
    }
}
