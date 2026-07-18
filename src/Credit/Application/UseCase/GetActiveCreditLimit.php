<?php

declare(strict_types=1);

namespace App\Credit\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Credit\Application\DTO\CreditLimitOutput;
use App\Credit\Application\DTO\GetActiveCreditLimitInput;
use App\Credit\Domain\Service\ActiveCreditLimitResolverInterface;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class GetActiveCreditLimit
{
    public function __construct(
        private ActiveCreditLimitResolverInterface $resolver,
        private CustomerRepository $customers,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private AuthorizationService $authorization,
    ) {
    }

    public function execute(int $customerId, GetActiveCreditLimitInput $input): ?CreditLimitOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::CreditLimitRead);
        $organization = $this->currentOrganization->currentOrganization();
        $customer = $this->customers->findById($organization, $customerId)
            ?? throw new DomainException('CUSTOMER_NOT_FOUND', 'Cliente não encontrado.', 404);
        $referenceDate = CreditLimitInput::date($input->referenceDate, 'reference_date');
        $creditLimit = $this->resolver->resolve($organization, $customer, $referenceDate);

        return null === $creditLimit ? null : CreditLimitOutput::fromEntity($creditLimit);
    }
}
