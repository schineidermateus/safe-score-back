<?php

declare(strict_types=1);

namespace App\Imports\Application\Validation;

use App\Customers\Domain\Enum\CustomerStatus;
use App\Imports\Application\Normalization\DocumentNormalizer;
use App\Imports\Application\Normalization\TextNormalizer;
use App\Imports\Application\Service\CustomerImportResolver;
use App\Imports\Domain\Enum\ImportAction;
use App\Imports\Domain\Enum\ImportType;
use App\Organizations\Domain\Entity\Organization;

final readonly class CustomerImportValidator implements ImportRowValidatorInterface
{
    public function __construct(private TextNormalizer $text, private DocumentNormalizer $documents, private CustomerImportResolver $resolver)
    {
    }

    public function supports(): ImportType
    {
        return ImportType::Customers;
    }

    public function validate(array $data, Organization $organization): ImportValidationResult
    {
        $externalId = $this->text->optional($data['external_id'] ?? null, 100, 'external_id');
        $document = $this->documents->optional($data['document'] ?? null);
        if (null === $externalId && null === $document) {
            throw new \InvalidArgumentException('Informe external_id ou document.');
        }
        $statusValue = strtoupper($this->text->optional($data['status'] ?? null, 20, 'status') ?? 'ACTIVE');
        $statusValue = match ($statusValue) {
            'ATIVO' => 'ACTIVE', 'INATIVO' => 'INACTIVE', default => $statusValue,
        };
        $status = CustomerStatus::tryFrom($statusValue) ?? throw new \InvalidArgumentException('status deve ser ACTIVE ou INACTIVE.');
        $normalized = ['external_id' => $externalId, 'legal_name' => $this->text->required($data['legal_name'] ?? null, 180, 'legal_name'), 'trade_name' => $this->text->optional($data['trade_name'] ?? null, 180, 'trade_name'), 'document' => $document, 'status' => $status->value];
        $customer = $this->resolver->resolve($organization, $externalId, $document);
        if (null === $customer) {
            $this->resolver->assertNoArchivedConflict($organization, $externalId, $document);

            return new ImportValidationResult($normalized, ImportAction::Create);
        }
        $identical = $customer->externalId() === $externalId && $customer->legalName() === $normalized['legal_name'] && $customer->tradeName() === $normalized['trade_name'] && $customer->document() === $document && $customer->status() === $status;

        return new ImportValidationResult($normalized, $identical ? ImportAction::Skip : ImportAction::Update, entityId: $customer->requireId());
    }
}
