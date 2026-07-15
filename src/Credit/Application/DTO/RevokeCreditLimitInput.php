<?php

declare(strict_types=1);

namespace App\Credit\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RevokeCreditLimitInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 1000)]
        public string $reason,
    ) {
    }
}
