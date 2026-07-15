<?php

declare(strict_types=1);

namespace App\Imports\Application\Processor;

use App\Audit\Application\AuditLogger;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Identity\Domain\Entity\User;
use App\Imports\Domain\Enum\ImportAction;
use App\Imports\Domain\Enum\ImportType;
use App\Organizations\Domain\Entity\Organization;
use App\Receivables\Application\UseCase\ReceivableSnapshot;
use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\Repository\ReceivableRepository;
use App\Receivables\Domain\ValueObject\ReceivableAmount;
use App\Shared\Domain\Exception\DomainException;

final readonly class ReceivableImportProcessor implements ImportProcessorInterface
{
    public function __construct(private ReceivableRepository $receivables, private CustomerRepository $customers, private AuditLogger $audit)
    {
    }

    public function supports(): ImportType
    {
        return ImportType::Receivables;
    }

    public function process(array $data, ImportAction $action, Organization $organization, User $user): ImportProcessResult
    {
        $customer = $this->customers->findById($organization, $data['customer_id']) ?? throw new DomainException('IMPORT_CUSTOMER_NOT_FOUND', 'Cliente não encontrado na organização atual.', 422);
        $existing = $this->receivables->findByExternalKey($organization, $data['source'], $data['external_id'], true);
        $issue = new \DateTimeImmutable($data['issue_date']);
        $due = new \DateTimeImmutable($data['due_date']);
        $amount = new ReceivableAmount($data['original_amount']);
        if (null !== $existing && $existing->customer()->requireId() === $customer->requireId() && $existing->documentNumber() === $data['document_number'] && $existing->issueDate() == $issue && $existing->dueDate() == $due && $existing->originalAmount() === (string) $amount && '0.00' === $existing->paidAmount()) {
            return new ImportProcessResult('Receivable', $existing->requireId(), true);
        }
        if (null !== $existing && ImportAction::Create === $action) {
            throw new DomainException('IMPORT_PROCESSING_CONFLICT', 'Recebível foi criado ou alterado após a validação.', 409);
        }
        if (ImportAction::Skip === $action) {
            throw new DomainException('IMPORT_PROCESSING_CONFLICT', 'Recebível idêntico não foi encontrado durante o processamento.', 409);
        }
        $now = new \DateTimeImmutable();
        $before = null;
        if (null === $existing) {
            if (ImportAction::Update === $action) {
                throw new DomainException('IMPORT_PROCESSING_CONFLICT', 'Recebível previsto para atualização não foi encontrado.', 409);
            }
            $receivable = Receivable::create($organization, $customer, $data['source'], $data['external_id'], $data['document_number'], $issue, $due, $amount, $now);
            $event = 'RECEIVABLE_IMPORTED_CREATED';
        } else {
            if ($existing->customer()->requireId() !== $customer->requireId() || '0.00' !== $existing->paidAmount()) {
                throw new DomainException('IMPORT_RECEIVABLE_CONFLICT', 'O recebível existente possui cliente ou pagamentos incompatíveis.', 409);
            }
            $receivable = $existing;
            $before = ReceivableSnapshot::fromEntity($receivable);
            $receivable->update($data['document_number'], $issue, $due, $amount, $now);
            $event = 'RECEIVABLE_IMPORTED_UPDATED';
        }
        $this->receivables->save($organization, $receivable);
        $this->audit->record($organization, $user, $event, 'Receivable', $receivable->requireId(), $before, ReceivableSnapshot::fromEntity($receivable), ['source' => 'CSV_IMPORT'], $now);

        return new ImportProcessResult('Receivable', $receivable->requireId(), false);
    }
}
