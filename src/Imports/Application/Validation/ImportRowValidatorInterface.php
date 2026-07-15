<?php

declare(strict_types=1);

namespace App\Imports\Application\Validation;

use App\Imports\Domain\Enum\ImportType;
use App\Organizations\Domain\Entity\Organization;

interface ImportRowValidatorInterface
{
    public function supports(): ImportType;

    /** @param array<string, string|null> $data */
    public function validate(array $data, Organization $organization): ImportValidationResult;
}
