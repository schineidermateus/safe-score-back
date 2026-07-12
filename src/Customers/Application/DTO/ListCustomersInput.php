<?php

declare(strict_types=1);

namespace App\Customers\Application\DTO;

use App\Customers\Domain\Enum\CustomerStatus;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ListCustomersInput
{
    public function __construct(
        #[Assert\Length(max: 180)]
        public ?string $search = null,
        #[Assert\Choice(callback: [CustomerStatus::class, 'values'])]
        public ?string $status = null,
        #[Assert\Positive]
        public int $page = 1,
        #[SerializedName('per_page')]
        #[Assert\Range(min: 1, max: 100)]
        public int $perPage = 20,
        #[Assert\Choice(choices: ['legal_name', '-legal_name', 'created_at', '-created_at'])]
        public string $sort = 'legal_name',
    ) {
    }
}
