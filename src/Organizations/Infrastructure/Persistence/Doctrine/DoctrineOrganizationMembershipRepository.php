<?php

declare(strict_types=1);

namespace App\Organizations\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Organizations\Domain\Enum\MembershipStatus;
use App\Organizations\Domain\Repository\OrganizationMembershipRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<OrganizationMembership> */
final class DoctrineOrganizationMembershipRepository extends ServiceEntityRepository implements OrganizationMembershipRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizationMembership::class);
    }

    public function save(OrganizationMembership $membership): void
    {
        $this->getEntityManager()->persist($membership);
        $this->getEntityManager()->flush();
    }

    public function findByIdAndOrganization(int $id, Organization $organization): ?OrganizationMembership
    {
        return $this->findOneBy(['id' => $id, 'organization' => $organization]);
    }

    public function findByOrganizationAndUser(Organization $organization, User $user): ?OrganizationMembership
    {
        return $this->findOneBy(['organization' => $organization, 'user' => $user]);
    }

    public function listByOrganization(Organization $organization): array
    {
        return $this->findBy(['organization' => $organization], ['id' => 'ASC']);
    }

    public function countActiveOwners(Organization $organization): int
    {
        return (int) $this->createQueryBuilder('membership')
            ->select('COUNT(membership.id)')
            ->andWhere('membership.organization = :organization')
            ->andWhere('membership.role = :role')
            ->andWhere('membership.status = :status')
            ->setParameter('organization', $organization)
            ->setParameter('role', MembershipRole::Owner)
            ->setParameter('status', MembershipStatus::Active)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
