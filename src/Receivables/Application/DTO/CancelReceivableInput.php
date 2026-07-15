<?php

declare(strict_types=1);

namespace App\Receivables\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CancelReceivableInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 1000)]
        public string $reason,
    ) {
    }
}
