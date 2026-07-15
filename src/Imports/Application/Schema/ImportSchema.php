<?php

declare(strict_types=1);

namespace App\Imports\Application\Schema;

use App\Imports\Domain\Enum\ImportType;

final readonly class ImportSchema
{
    /**
     * @param list<string>       $fields
     * @param list<string>       $required
     * @param list<list<string>> $oneOf
     */
    public function __construct(public ImportType $type, public array $fields, public array $required, public array $oneOf)
    {
    }
}
