<?php

declare(strict_types=1);

namespace App\Receivables\Application\UseCase;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Receivables\Application\DTO\CancelReceivableInput;
use App\Receivables\Application\DTO\ReceivableOutput;
use App\Receivables\Application\Service\ReceivableOutputFactory;
use App\Receivables\Domain\Repository\ReceivableRepository;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class CancelReceivable
{
    public function __construct(private ReceivableRepository $receivables, private CurrentOrganizationProviderInterface $currentOrganization,
        private CurrentUserProviderInterface $currentUser, private AuthorizationService $authorization,
        private TransactionManagerInterface $transactions, private AuditLogger $audit, private ReceivableOutputFactory $output)
    {
    }

    public function execute(int $id, CancelReceivableInput $input): ReceivableOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::ReceivableCancel);
        $organization = $this->currentOrganization->currentOrganization();
        $user = $this->currentUser->currentUser();

        return $this->transactions->transactional(function () use ($id, $input, $organization, $user): ReceivableOutput {
            $receivable = $this->receivables->findByIdAndOrganizationForUpdate($id, $organization) ?? throw new DomainException('RECEIVABLE_NOT_FOUND', 'Recebível não encontrado.', 404);
            $before = ReceivableSnapshot::fromEntity($receivable);
            $now = new \DateTimeImmutable();
            try {
                $receivable->cancel($user, $input->reason, $now);
            } catch (\DomainException $e) {
                $code = str_contains($e->getMessage(), 'already') ? 'RECEIVABLE_ALREADY_CANCELLED' : 'RECEIVABLE_NOT_EDITABLE';
                throw new DomainException($code, 'Cancelamento não permitido.', 409);
            } catch (\InvalidArgumentException) {
                throw new DomainException('RECEIVABLE_INVALID_CANCELLATION_REASON', 'O motivo é obrigatório.', 422, 'reason');
            }
            $this->receivables->save($organization, $receivable);
            $this->audit->record($organization, $user, 'RECEIVABLE_CANCELLED', 'Receivable', $id, $before, ReceivableSnapshot::fromEntity($receivable), ['reason' => $receivable->cancellationReason()], $now);

            return $this->output->create($receivable, new \DateTimeImmutable('today'));
        });
    }
}
