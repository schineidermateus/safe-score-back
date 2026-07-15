<?php

declare(strict_types=1);

namespace App\Credit\Application\UseCase;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Credit\Application\DTO\CreateCreditLimitInput;
use App\Credit\Application\DTO\CreditLimitOutput;
use App\Credit\Domain\Entity\CreditLimit;
use App\Credit\Domain\Repository\CreditLimitRepository;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class CreateCreditLimit
{
    public function __construct(
        private CreditLimitRepository $creditLimits,
        private CustomerRepository $customers,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private CurrentUserProviderInterface $currentUser,
        private AuthorizationService $authorization,
        private TransactionManagerInterface $transactions,
        private AuditLogger $audit,
    ) {
    }

    public function execute(int $customerId, CreateCreditLimitInput $input): CreditLimitOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::CreditLimitWrite);
        $organization = $this->currentOrganization->currentOrganization();
        $user = $this->currentUser->currentUser();
        $amount = CreditLimitInput::amount($input->amount);
        $validFrom = CreditLimitInput::date($input->validFrom, 'valid_from');
        $validUntil = CreditLimitInput::optionalDate($input->validUntil, 'valid_until');
        $reason = CreditLimitInput::reason($input->reason);
        if (null !== $validUntil && $validUntil < $validFrom) {
            throw new DomainException('CREDIT_LIMIT_INVALID_PERIOD', 'A data final não pode ser anterior à data inicial.', 422, 'valid_until');
        }

        return $this->transactions->transactional(function () use ($organization, $user, $customerId, $amount, $validFrom, $validUntil, $reason): CreditLimitOutput {
            $customer = $this->customers->findById($organization, $customerId)
                ?? throw new DomainException('CUSTOMER_NOT_FOUND', 'Cliente não encontrado.', 404);
            $this->creditLimits->lockCustomer($customer, $organization);
            if ($this->creditLimits->existsOverlappingActivePeriod($customer, $organization, $validFrom, $validUntil)) {
                throw new DomainException('CREDIT_LIMIT_OVERLAP', 'Já existe um limite ativo no período informado.', 409, 'valid_from');
            }

            $now = new \DateTimeImmutable();
            $creditLimit = CreditLimit::createActive(
                $organization,
                $customer,
                $amount,
                $validFrom,
                $validUntil,
                $reason,
                $user,
                $now,
            );
            $this->creditLimits->save($organization, $creditLimit);
            $this->audit->record(
                $organization,
                $user,
                'CREDIT_LIMIT_CREATED',
                'CreditLimit',
                $creditLimit->requireId(),
                null,
                CreditLimitSnapshot::fromEntity($creditLimit),
                ['reason' => $creditLimit->reason()],
                $now,
            );

            return CreditLimitOutput::fromEntity($creditLimit);
        });
    }
}
