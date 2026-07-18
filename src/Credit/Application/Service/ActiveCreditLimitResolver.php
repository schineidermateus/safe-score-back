<?php

declare(strict_types=1);

namespace App\Credit\Application\Service;

use App\Credit\Domain\Entity\CreditLimit;
use App\Credit\Domain\Repository\CreditLimitRepository;
use App\Credit\Domain\Service\ActiveCreditLimitResolverInterface;
use App\Customers\Domain\Entity\Customer;
use App\Organizations\Domain\Entity\Organization;
use App\Shared\Domain\Exception\DomainException;

final readonly class ActiveCreditLimitResolver implements ActiveCreditLimitResolverInterface
{
    public function __construct(private CreditLimitRepository $repository)
    {
    }

    public function resolve(
        Organization $organization,
        Customer $customer,
        \DateTimeImmutable $referenceDate,
    ): ?CreditLimit {
        if ($customer->organization() !== $organization) {
            throw new DomainException('CUSTOMER_NOT_FOUND', 'Cliente não encontrado.', 404);
        }

        $limits = $this->repository->findActiveByCustomerAndDate($customer, $organization, $referenceDate);
        if (count($limits) > 1) {
            throw new DomainException('CREDIT_LIMIT_INTEGRITY_ERROR', 'Foram encontrados limites de crédito vigentes conflitantes.', 500);
        }

        return $limits[0] ?? null;
    }
}
