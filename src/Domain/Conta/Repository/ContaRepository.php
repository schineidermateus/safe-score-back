<?php

namespace App\Domain\Conta\Repository;

use App\Domain\Conta\Entity\Conta;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conta::class);
    }

    public function save(Conta $conta, bool $flush = true): void
    {
        $this->getEntityManager()->persist($conta);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
