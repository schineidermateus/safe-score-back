<?php

declare(strict_types=1);

namespace App\Credit\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Credit\Application\DTO\CreditLimitListOutput;
use App\Credit\Application\DTO\CreditLimitOutput;
use App\Credit\Application\DTO\ListCreditLimitsInput;
use App\Credit\Domain\Repository\CreditLimitRepository;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class ListCustomerCreditLimitHistory
{
    public function __construct(
        private CreditLimitRepository $creditLimits,
        private CustomerRepository $customers,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private AuthorizationService $authorization,
    ) {
    }

    public function execute(int $customerId, ListCreditLimitsInput $input): CreditLimitListOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::CreditLimitRead);
        $organization = $this->currentOrganization->currentOrganization();
        $customer = $this->customers->findById($organization, $customerId)
            ?? throw new DomainException('CUSTOMER_NOT_FOUND', 'Cliente não encontrado.', 404);
        $limits = $this->creditLimits->findHistoryByCustomerAndOrganization(
            $customer,
            $organization,
            $input->page,
            $input->perPage,
        );

        return new CreditLimitListOutput(
            array_map(CreditLimitOutput::fromEntity(...), $limits),
            $input->page,
            $input->perPage,
            $this->creditLimits->countHistoryByCustomerAndOrganization($customer, $organization),
        );
    }
}
