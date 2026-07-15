<?php

declare(strict_types=1);

namespace App\Imports\Application\Validation;

use App\Imports\Domain\Enum\ImportAction;

final readonly class ImportValidationResult
{
    /**
     * @param array<string, mixed>                                                          $normalized
     * @param list<array{code: string, field?: string, message: string, severity?: string}> $errors
     */
    public function __construct(public array $normalized, public ImportAction $action, public array $errors = [], public ?int $entityId = null)
    {
    }
}
