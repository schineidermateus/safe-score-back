<?php

declare(strict_types=1);

namespace App\Credit\Application\DTO;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetActiveCreditLimitInput
{
    public function __construct(
        #[SerializedName('reference_date')]
        #[Assert\NotBlank]
        #[Assert\Date]
        public ?string $referenceDate = null,
    ) {
    }
}
