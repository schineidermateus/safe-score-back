<?php

declare(strict_types=1);

namespace App\Credit\Application\DTO;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ListCreditLimitsInput
{
    public function __construct(
        #[Assert\Positive]
        public int $page = 1,
        #[SerializedName('per_page')]
        #[Assert\Range(min: 1, max: 100)]
        public int $perPage = 20,
    ) {
    }
}
