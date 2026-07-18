<?php

declare(strict_types=1);

namespace App\Credit\Application\DTO;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateCreditLimitInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^(?:0|[1-9]\d{0,16})(?:\.\d{1,2})?$/')]
        public string $amount,
        #[SerializedName('valid_from')]
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $validFrom,
        #[SerializedName('valid_until')]
        #[Assert\Date]
        public ?string $validUntil = null,
        #[Assert\NotBlank]
        #[Assert\Length(max: 1000)]
        public string $reason = '',
    ) {
    }
}
