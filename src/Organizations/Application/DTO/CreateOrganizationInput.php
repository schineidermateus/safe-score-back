<?php

declare(strict_types=1);

namespace App\Organizations\Application\DTO;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateOrganizationInput
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
        #[Assert\NotBlank]
        public string $timezone = 'America/Sao_Paulo',
        #[Assert\Currency]
        public string $currency = 'BRL',
    ) {
    }
}
