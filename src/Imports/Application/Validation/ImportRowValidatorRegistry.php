<?php

declare(strict_types=1);

namespace App\Imports\Application\Validation;

use App\Imports\Domain\Enum\ImportType;

final readonly class ImportRowValidatorRegistry
{
    public function __construct(private CustomerImportValidator $customers, private CreditLimitImportValidator $creditLimits, private ReceivableImportValidator $receivables)
    {
    }

    public function get(ImportType $type): ImportRowValidatorInterface
    {
        return match ($type) {
            ImportType::Customers => $this->customers, ImportType::CreditLimits => $this->creditLimits, ImportType::Receivables => $this->receivables,
        };
    }
}
