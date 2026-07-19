<?php

declare(strict_types=1);

namespace App\Imports\Application\Processor;

use App\Imports\Domain\Enum\ImportType;
use App\Shared\Domain\Exception\DomainException;

final readonly class ImportProcessorRegistry
{
    public function get(ImportType $type): ImportProcessorInterface
    {
        throw new DomainException('IMPORT_TYPE_NOT_IMPLEMENTED', sprintf('O tipo %s ainda nÃ£o possui processor.', $type->value), 422, 'type');
    }
}
