<?php

declare(strict_types=1);

namespace App\Organizations\Infrastructure\Persistence\Doctrine;

use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Repository\OrganizationRepository;
use App\Shared\Domain\Exception\DomainException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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

        try {
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException) {
            throw new DomainException('ORGANIZATION_DOCUMENT_ALREADY_EXISTS', 'Já existe uma organização com este documento.', 409, 'document');
        }
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
