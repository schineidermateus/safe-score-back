<?php

declare(strict_types=1);

namespace App\Organizations\Infrastructure\Persistence\Doctrine;

use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Repository\OrganizationRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Organization> */
final class DoctrineOrganizationRepository extends ServiceEntityRepository implements OrganizationRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    public function save(Organization $organization): void
    {
        $this->getEntityManager()->persist($organization);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id): ?Organization
    {
        return $this->find($id);
    }

    public function findByDocument(string $document): ?Organization
    {
        return $this->findOneBy(['document' => $document]);
    }
}
