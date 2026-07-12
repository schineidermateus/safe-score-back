<?php

declare(strict_types=1);

namespace App\Customers\Application\DTO;

use App\Customers\Domain\Enum\CustomerStatus;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateCustomerInput
{
    public function __construct(
        #[SerializedName('legal_name')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 180)]
        public string $legalName,
        #[SerializedName('trade_name')]
        #[Assert\Length(max: 180)]
        public ?string $tradeName = null,
        #[Assert\Length(max: 18)]
        public ?string $document = null,
        #[SerializedName('external_id')]
        #[Assert\Length(max: 100)]
        public ?string $externalId = null,
        #[Assert\Length(max: 100)]
        public ?string $segment = null,
        #[SerializedName('account_manager')]
        #[Assert\Length(max: 120)]
        public ?string $accountManager = null,
        #[Assert\Choice(callback: [CustomerStatus::class, 'values'])]
        public string $status = 'ACTIVE',
    ) {
    }
}
