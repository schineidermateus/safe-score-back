<?php

declare(strict_types=1);

namespace App\Receivables\Infrastructure\Persistence\Doctrine;

use App\Organizations\Domain\Entity\Organization;
use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\Entity\ReceivablePayment;
use App\Receivables\Domain\Repository\ReceivablePaymentRepository;
use App\Shared\Domain\Exception\DomainException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ReceivablePayment> */
final class DoctrineReceivablePaymentRepository extends ServiceEntityRepository implements ReceivablePaymentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReceivablePayment::class);
    }

    public function save(Organization $organization, ReceivablePayment $payment): void
    {
        if ($payment->organization() !== $organization || $payment->receivable()->organization() !== $organization) {
            throw new DomainException('RECEIVABLE_TENANT_MISMATCH', 'Pagamento não pertence à organização atual.', 403);
        }
        $this->getEntityManager()->persist($payment);
        $this->getEntityManager()->flush();
    }

    public function listByReceivableAndOrganization(Receivable $receivable, Organization $organization): array
    {
        if ($receivable->organization() !== $organization) {
            throw new DomainException('RECEIVABLE_NOT_FOUND', 'Recebível não encontrado.', 404);
        }

        return $this->findBy(['organization' => $organization, 'receivable' => $receivable], ['paymentDate' => 'ASC', 'id' => 'ASC']);
    }
}
