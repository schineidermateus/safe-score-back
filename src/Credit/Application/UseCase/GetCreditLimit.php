<?php

declare(strict_types=1);

namespace App\Credit\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Credit\Application\DTO\CreditLimitOutput;
use App\Credit\Domain\Repository\CreditLimitRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class GetCreditLimit
{
    public function __construct(
        private CreditLimitRepository $repository,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private AuthorizationService $authorization,
    ) {
    }

    public function execute(int $creditLimitId): CreditLimitOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::CreditLimitRead);
        $creditLimit = $this->repository->findByIdAndOrganization(
            $creditLimitId,
            $this->currentOrganization->currentOrganization(),
        ) ?? throw new DomainException('CREDIT_LIMIT_NOT_FOUND', 'Limite de crédito não encontrado.', 404);

        return CreditLimitOutput::fromEntity($creditLimit);
    }
}
