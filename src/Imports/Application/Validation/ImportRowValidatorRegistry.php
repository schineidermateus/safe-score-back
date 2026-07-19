<?php

declare(strict_types=1);

namespace App\Imports\Application\Validation;

use App\Imports\Domain\Enum\ImportType;
use App\Shared\Domain\Exception\DomainException;

final readonly class ImportRowValidatorRegistry
{
    public function get(ImportType $type): ImportRowValidatorInterface
    {
        throw new DomainException('IMPORT_TYPE_NOT_IMPLEMENTED', sprintf('O tipo %s ainda nÃ£o possui validator.', $type->value), 422, 'type');
    }
}
