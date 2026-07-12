<?php

namespace App\Domain\Usuario\Repository;

use App\Domain\Usuario\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UsuarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Usuario::class);
    }

    public function findOneByEmail(string $email): ?Usuario
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Persiste e flush automático.
     */
    public function save(Usuario $user, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($user);

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Remove um usuário.
     */
    public function remove(Usuario $user, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->remove($user);

        if ($flush) {
            $em->flush();
        }
    }
}
