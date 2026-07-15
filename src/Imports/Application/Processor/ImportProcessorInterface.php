<?php

declare(strict_types=1);

namespace App\Imports\Application\Processor;

use App\Identity\Domain\Entity\User;
use App\Imports\Domain\Enum\ImportAction;
use App\Imports\Domain\Enum\ImportType;
use App\Organizations\Domain\Entity\Organization;

interface ImportProcessorInterface
{
    public function supports(): ImportType;

    /** @param array<string, mixed> $data */
    public function process(array $data, ImportAction $action, Organization $organization, User $user): ImportProcessResult;
}
