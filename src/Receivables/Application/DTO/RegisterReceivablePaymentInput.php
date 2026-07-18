<?php

declare(strict_types=1);

namespace App\Receivables\Application\DTO;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterReceivablePaymentInput
{
    public function __construct(
        #[Assert\Regex(pattern: '/^(?:0|[1-9]\d{0,16})(?:\.\d{1,2})?$/')]
        public string $amount,
        #[SerializedName('payment_date')]
        #[Assert\Date]
        public string $paymentDate,
    ) {
    }
}
