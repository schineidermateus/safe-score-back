<?php

declare(strict_types=1);

namespace App\Imports\Application\Validation;

use App\Imports\Application\Normalization\DateNormalizer;
use App\Imports\Application\Normalization\DocumentNormalizer;
use App\Imports\Application\Normalization\MoneyNormalizer;
use App\Imports\Application\Normalization\TextNormalizer;
use App\Imports\Application\Service\CustomerImportResolver;
use App\Imports\Domain\Enum\ImportAction;
use App\Imports\Domain\Enum\ImportType;
use App\Organizations\Domain\Entity\Organization;
use App\Receivables\Domain\Enum\ReceivableStatus;
use App\Receivables\Domain\Repository\ReceivableRepository;
use App\Receivables\Domain\ValueObject\ReceivableAmount;

final readonly class ReceivableImportValidator implements ImportRowValidatorInterface
{
    public function __construct(private TextNormalizer $text, private DocumentNormalizer $documents, private MoneyNormalizer $money, private DateNormalizer $dates, private CustomerImportResolver $customers, private ReceivableRepository $receivables)
    {
    }

    public function supports(): ImportType
    {
        return ImportType::Receivables;
    }

    public function validate(array $data, Organization $organization): ImportValidationResult
    {
        $customerExternal = $this->text->optional($data['customer_external_id'] ?? null, 100, 'customer_external_id');
        $customerDocument = $this->documents->optional($data['customer_document'] ?? null);
        $customer = $this->customers->require($organization, $customerExternal, $customerDocument);
        $source = strtoupper($this->text->required($data['source'] ?? null, 50, 'source'));
        if (in_array($source, ['MANUAL', 'FIXTURE'], true)) {
            throw new \InvalidArgumentException('source MANUAL e FIXTURE são reservados.');
        }
        $externalId = $this->text->required($data['external_id'] ?? null, 150, 'external_id');
        $documentNumber = $this->text->required($data['document_number'] ?? null, 100, 'document_number');
        $issueDate = $this->dates->required($data['issue_date'] ?? null, 'issue_date');
        $dueDate = $this->dates->required($data['due_date'] ?? null, 'due_date');
        if ($dueDate < $issueDate) {
            throw new \InvalidArgumentException('due_date não pode ser anterior a issue_date.');
        }
        $originalAmount = new ReceivableAmount($this->money->normalize($data['original_amount'] ?? null, 'original_amount'));
        if (!$originalAmount->isPositive()) {
            throw new \InvalidArgumentException('original_amount deve ser maior que zero.');
        }
        $original = (string) $originalAmount;
        $open = null === ($data['open_amount'] ?? null) || '' === trim((string) $data['open_amount']) ? $original : (string) new ReceivableAmount($this->money->normalize($data['open_amount'], 'open_amount'));
        $paid = null === ($data['paid_amount'] ?? null) || '' === trim((string) $data['paid_amount']) ? '0.00' : (string) new ReceivableAmount($this->money->normalize($data['paid_amount'], 'paid_amount'));
        $paymentDate = $this->dates->optional($data['payment_date'] ?? null, 'payment_date');
        $status = strtoupper($this->text->optional($data['status'] ?? null, 20, 'status') ?? 'OPEN');
        if ($open !== $original || '0.00' !== $paid || null !== $paymentDate || !in_array($status, ['OPEN', 'OVERDUE'], true)) {
            throw new \InvalidArgumentException('O MVP importa somente recebíveis inicialmente abertos, sem pagamentos.');
        }
        $normalized = ['customer_id' => $customer->requireId(), 'customer_external_id' => $customerExternal, 'customer_document' => $customerDocument, 'source' => $source, 'external_id' => $externalId, 'document_number' => $documentNumber, 'issue_date' => $issueDate->format('Y-m-d'), 'due_date' => $dueDate->format('Y-m-d'), 'original_amount' => $original, 'open_amount' => $original, 'paid_amount' => '0.00', 'payment_date' => null, 'status' => 'OPEN'];
        $existing = $this->receivables->findByExternalKey($organization, $source, $externalId);
        if (null === $existing) {
            return new ImportValidationResult($normalized, ImportAction::Create);
        }
        if ($existing->customer()->requireId() !== $customer->requireId()) {
            throw new \InvalidArgumentException('A chave externa pertence a outro cliente.');
        }
        if ('0.00' !== $existing->paidAmount() || in_array($existing->status(), [ReceivableStatus::Paid, ReceivableStatus::Cancelled], true)) {
            throw new \InvalidArgumentException('Recebível com pagamento ou encerrado não pode ser atualizado pela importação.');
        }
        $identical = $existing->documentNumber() === $documentNumber && $existing->issueDate() == $issueDate && $existing->dueDate() == $dueDate && $existing->originalAmount() === $original;

        return new ImportValidationResult($normalized, $identical ? ImportAction::Skip : ImportAction::Update, entityId: $existing->requireId());
    }
}
