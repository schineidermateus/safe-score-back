<?php

namespace App\Domain\CartaoCredito\Repository;

use App\Domain\CartaoCredito\Entity\CartaoCredito;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CartaoCreditoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartaoCredito::class);
    }
}
