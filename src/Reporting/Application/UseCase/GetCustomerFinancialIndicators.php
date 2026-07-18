<?php

declare(strict_types=1);

namespace App\Reporting\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Reporting\Application\DTO\CustomerFinancialIndicators;
use App\Reporting\Application\DTO\GetCustomerFinancialIndicatorsInput;
use App\Reporting\Application\Provider\CustomerFinancialIndicatorsProvider;
use App\Reporting\Domain\ValueObject\ReferenceDate;
use App\Shared\Domain\Exception\DomainException;

final readonly class GetCustomerFinancialIndicators
{
    public function __construct(
        private CustomerFinancialIndicatorsProvider $provider,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private AuthorizationService $authorization,
    ) {
    }

    public function execute(int $customerId, GetCustomerFinancialIndicatorsInput $input): CustomerFinancialIndicators
    {
        $this->authorization->assertGranted(AuthorizationAction::FinancialIndicatorsRead);
        if (null === $input->referenceDate) {
            throw new DomainException('REFERENCE_DATE_REQUIRED', 'A data de referência é obrigatória.', 422, 'reference_date');
        }
        try {
            $referenceDate = ReferenceDate::fromString($input->referenceDate);
        } catch (\InvalidArgumentException) {
            throw new DomainException('REFERENCE_DATE_INVALID', 'A data de referência é inválida.', 422, 'reference_date');
        }

        return $this->provider->getForCustomer($this->currentOrganization->currentOrganization(), $customerId, $referenceDate);
    }
}
