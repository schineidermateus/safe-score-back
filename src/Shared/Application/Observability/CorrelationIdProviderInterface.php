<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability;

interface CorrelationIdProviderInterface
{
    public function current(): string;
}
