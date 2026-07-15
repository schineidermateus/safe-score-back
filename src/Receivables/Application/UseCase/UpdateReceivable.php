<?php

declare(strict_types=1);

namespace App\Receivables\Application\UseCase;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Receivables\Application\DTO\ReceivableOutput;
use App\Receivables\Application\DTO\UpdateReceivableInput;
use App\Receivables\Application\Service\ReceivableOutputFactory;
use App\Receivables\Domain\Repository\ReceivableRepository;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class UpdateReceivable
{
    public function __construct(private ReceivableRepository $receivables, private CurrentOrganizationProviderInterface $currentOrganization,
        private CurrentUserProviderInterface $currentUser, private AuthorizationService $authorization,
        private TransactionManagerInterface $transactions, private AuditLogger $audit, private ReceivableOutputFactory $output)
    {
    }

    public function execute(int $id, UpdateReceivableInput $input): ReceivableOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::ReceivableWrite);
        $organization = $this->currentOrganization->currentOrganization();
        $user = $this->currentUser->currentUser();
        $issue = ReceivableInput::date($input->issueDate, 'issue_date');
        $due = ReceivableInput::date($input->dueDate, 'due_date');
        ReceivableInput::assertValidPeriod($issue, $due);
        $amount = ReceivableInput::amount($input->originalAmount, 'original_amount');

        return $this->transactions->transactional(function () use ($id, $input, $organization, $user, $issue, $due, $amount): ReceivableOutput {
            $receivable = $this->receivables->findByIdAndOrganizationForUpdate($id, $organization) ?? throw new DomainException('RECEIVABLE_NOT_FOUND', 'Recebível não encontrado.', 404);
            $before = ReceivableSnapshot::fromEntity($receivable);
            $now = new \DateTimeImmutable();
            try {
                $receivable->update($input->documentNumber, $issue, $due, $amount, $now);
            } catch (\DomainException $e) {
                throw new DomainException('RECEIVABLE_NOT_EDITABLE', $e->getMessage(), 409);
            } catch (\InvalidArgumentException) {
                throw new DomainException('RECEIVABLE_INVALID_DATES', 'Os dados do recebível são inválidos.', 422);
            }
            $this->receivables->save($organization, $receivable);
            $this->audit->record($organization, $user, 'RECEIVABLE_UPDATED', 'Receivable', $id, $before, ReceivableSnapshot::fromEntity($receivable), null, $now);

            return $this->output->create($receivable, new \DateTimeImmutable('today'));
        });
    }
}
