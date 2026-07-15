<?php

declare(strict_types=1);

namespace App\Credit\Application\UseCase;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Credit\Application\DTO\CreditLimitOutput;
use App\Credit\Application\DTO\RevokeCreditLimitInput;
use App\Credit\Domain\Enum\CreditLimitStatus;
use App\Credit\Domain\Repository\CreditLimitRepository;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class RevokeCreditLimit
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

    public function execute(int $creditLimitId, RevokeCreditLimitInput $input): CreditLimitOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::CreditLimitRevoke);
        $organization = $this->currentOrganization->currentOrganization();
        $user = $this->currentUser->currentUser();
        $reason = CreditLimitInput::reason($input->reason);

        return $this->transactions->transactional(function () use ($organization, $user, $creditLimitId, $reason): CreditLimitOutput {
            $creditLimit = $this->repository->findByIdAndOrganization($creditLimitId, $organization)
                ?? throw new DomainException('CREDIT_LIMIT_NOT_FOUND', 'Limite de crédito não encontrado.', 404);
            if (CreditLimitStatus::Revoked === $creditLimit->status()) {
                throw new DomainException('CREDIT_LIMIT_ALREADY_REVOKED', 'O limite de crédito já foi revogado.', 409);
            }
            if (CreditLimitStatus::Expired === $creditLimit->status()) {
                throw new DomainException('CREDIT_LIMIT_NOT_EDITABLE', 'Limite expirado não pode ser revogado.', 409);
            }

            $this->repository->lockCustomer($creditLimit->customer(), $organization);
            $before = CreditLimitSnapshot::fromEntity($creditLimit);
            $now = new \DateTimeImmutable();
            $creditLimit->revoke($reason, $now);
            $this->repository->save($organization, $creditLimit);
            $after = CreditLimitSnapshot::fromEntity($creditLimit);
            $this->audit->record(
                $organization,
                $user,
                'CREDIT_LIMIT_REVOKED',
                'CreditLimit',
                $creditLimit->requireId(),
                $before,
                $after,
                ['reason' => $reason],
                $now,
            );

            return CreditLimitOutput::fromEntity($creditLimit);
        });
    }
}
