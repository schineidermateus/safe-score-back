<?php

declare(strict_types=1);

namespace App\Credit\Application\UseCase;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Credit\Application\DTO\CreditLimitOutput;
use App\Credit\Application\DTO\UpdateCreditLimitInput;
use App\Credit\Domain\Enum\CreditLimitStatus;
use App\Credit\Domain\Repository\CreditLimitRepository;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class UpdateCreditLimit
{
    public function __construct(
        private CreditLimitRepository $repository,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private CurrentUserProviderInterface $currentUser,
        private AuthorizationService $authorization,
        private TransactionManagerInterface $transactions,
        private AuditLogger $audit,
    ) {
    }

    public function execute(int $creditLimitId, UpdateCreditLimitInput $input): CreditLimitOutput
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

        return $this->transactions->transactional(function () use ($organization, $user, $creditLimitId, $amount, $validFrom, $validUntil, $reason): CreditLimitOutput {
            $creditLimit = $this->repository->findByIdAndOrganization($creditLimitId, $organization)
                ?? throw new DomainException('CREDIT_LIMIT_NOT_FOUND', 'Limite de crédito não encontrado.', 404);
            if (!in_array($creditLimit->status(), [CreditLimitStatus::Draft, CreditLimitStatus::Active], true)) {
                throw new DomainException('CREDIT_LIMIT_NOT_EDITABLE', 'O limite de crédito não pode mais ser editado.', 409);
            }

            $this->repository->lockCustomer($creditLimit->customer(), $organization);
            if (
                CreditLimitStatus::Active === $creditLimit->status()
                && $this->repository->existsOverlappingActivePeriod(
                    $creditLimit->customer(),
                    $organization,
                    $validFrom,
                    $validUntil,
                    $creditLimit->requireId(),
                )
            ) {
                throw new DomainException('CREDIT_LIMIT_OVERLAP', 'Já existe um limite ativo no período informado.', 409, 'valid_from');
            }

            $before = CreditLimitSnapshot::fromEntity($creditLimit);
            $now = new \DateTimeImmutable();
            $creditLimit->update($amount, $validFrom, $validUntil, $reason, $now);
            $this->repository->save($organization, $creditLimit);
            $after = CreditLimitSnapshot::fromEntity($creditLimit);
            $this->audit->record(
                $organization,
                $user,
                'CREDIT_LIMIT_UPDATED',
                'CreditLimit',
                $creditLimit->requireId(),
                $before,
                $after,
                ['reason' => $creditLimit->reason()],
                $now,
            );

            return CreditLimitOutput::fromEntity($creditLimit);
        });
    }
}
