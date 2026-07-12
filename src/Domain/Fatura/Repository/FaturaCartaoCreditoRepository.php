<?php

namespace App\Domain\Fatura\Repository;

use App\Domain\Fatura\Entity\FaturaCartaoCredito;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FaturaCartaoCreditoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaturaCartaoCredito::class);
    }
}
