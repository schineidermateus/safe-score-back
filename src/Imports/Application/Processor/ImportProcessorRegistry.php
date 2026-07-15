<?php

declare(strict_types=1);

namespace App\Imports\Application\Processor;

use App\Imports\Domain\Enum\ImportType;

final readonly class ImportProcessorRegistry
{
    public function __construct(private CustomerImportProcessor $customers, private CreditLimitImportProcessor $creditLimits, private ReceivableImportProcessor $receivables)
    {
    }

    public function get(ImportType $type): ImportProcessorInterface
    {
        return match ($type) {
            ImportType::Customers => $this->customers, ImportType::CreditLimits => $this->creditLimits, ImportType::Receivables => $this->receivables,
        };
    }
}
