<?php

declare(strict_types=1);

namespace App\Imports\Application\Validation;

use App\Credit\Domain\Repository\CreditLimitRepository;
use App\Credit\Domain\ValueObject\MoneyAmount;
use App\Imports\Application\Normalization\DateNormalizer;
use App\Imports\Application\Normalization\DocumentNormalizer;
use App\Imports\Application\Normalization\MoneyNormalizer;
use App\Imports\Application\Normalization\TextNormalizer;
use App\Imports\Application\Service\CustomerImportResolver;
use App\Imports\Domain\Enum\ImportAction;
use App\Imports\Domain\Enum\ImportType;
use App\Organizations\Domain\Entity\Organization;
use App\Shared\Domain\Exception\DomainException;

final readonly class CreditLimitImportValidator implements ImportRowValidatorInterface
{
    public function __construct(private TextNormalizer $text, private DocumentNormalizer $documents, private MoneyNormalizer $money, private DateNormalizer $dates, private CustomerImportResolver $customers, private CreditLimitRepository $limits)
    {
    }

    public function supports(): ImportType
    {
        return ImportType::CreditLimits;
    }

    public function validate(array $data, Organization $organization): ImportValidationResult
    {
        $externalId = $this->text->optional($data['customer_external_id'] ?? null, 100, 'customer_external_id');
        $document = $this->documents->optional($data['customer_document'] ?? null);
        $customer = $this->customers->require($organization, $externalId, $document);
        $amount = (string) new MoneyAmount($this->money->normalize($data['amount'] ?? null, 'amount'));
        $from = $this->dates->required($data['valid_from'] ?? null, 'valid_from');
        $until = $this->dates->optional($data['valid_until'] ?? null, 'valid_until');
        if (null !== $until && $until < $from) {
            throw new \InvalidArgumentException('valid_until não pode ser anterior a valid_from.');
        }
        $status = strtoupper($this->text->optional($data['status'] ?? null, 20, 'status') ?? 'ACTIVE');
        if ('ATIVO' === $status) {
            $status = 'ACTIVE';
        }
        if ('ACTIVE' !== $status) {
            throw new \InvalidArgumentException('A importação inicial aceita somente status ACTIVE.');
        }
        $reason = $this->text->required($data['reason'] ?? null, 1000, 'reason');
        $normalized = ['customer_id' => $customer->requireId(), 'customer_external_id' => $externalId, 'customer_document' => $document, 'amount' => $amount, 'valid_from' => $from->format('Y-m-d'), 'valid_until' => $until?->format('Y-m-d'), 'status' => $status, 'reason' => $reason];
        $identical = $this->limits->findIdenticalActive($customer, $organization, $amount, $from, $until, $reason);
        if (null !== $identical) {
            return new ImportValidationResult($normalized, ImportAction::Skip, entityId: $identical->requireId());
        }
        if ($this->limits->existsOverlappingActivePeriod($customer, $organization, $from, $until)) {
            throw new DomainException('IMPORT_CREDIT_LIMIT_OVERLAP', 'Existe um limite ativo conflitante no período.', 422);
        }

        return new ImportValidationResult($normalized, ImportAction::Create);
    }
}
