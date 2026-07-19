<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entity\ExternalIdentity;
use App\Identity\Domain\Enum\ExternalIdentityStatus;
use App\Identity\Domain\Repository\ExternalIdentityRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ExternalIdentity> */
final class DoctrineExternalIdentityRepository extends ServiceEntityRepository implements ExternalIdentityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExternalIdentity::class);
    }

    public function save(ExternalIdentity $identity): void
    {
        $this->getEntityManager()->persist($identity);
        $this->getEntityManager()->flush();
    }

    public function findActive(string $issuer, string $subject): ?ExternalIdentity
    {
        return $this->findOneBy([
            'issuer' => $issuer,
            'subject' => $subject,
            'status' => ExternalIdentityStatus::Active,
        ]);
    }
}
