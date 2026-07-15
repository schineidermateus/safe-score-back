<?php

declare(strict_types=1);

namespace App\Receivables\Application\DTO;

use App\Receivables\Domain\Enum\AgingBucket;
use App\Receivables\Domain\Enum\ReceivableStatus;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ListReceivablesInput
{
    public function __construct(
        #[SerializedName('customer_id')]
        #[Assert\Positive]
        public ?int $customerId = null,
        #[Assert\Choice(callback: [ReceivableStatus::class, 'values'])]
        public ?string $status = null,
        public ?bool $overdue = null,
        #[SerializedName('due_date_from')]
        #[Assert\Date]
        public ?string $dueDateFrom = null,
        #[SerializedName('due_date_to')]
        #[Assert\Date]
        public ?string $dueDateTo = null,
        #[SerializedName('aging_bucket')]
        #[Assert\Choice(callback: [AgingBucket::class, 'values'])]
        public ?string $agingBucket = null,
        #[SerializedName('amount_min')]
        public ?string $amountMin = null,
        #[SerializedName('amount_max')]
        public ?string $amountMax = null,
        #[Assert\Length(max: 180)]
        public ?string $search = null,
        #[SerializedName('reference_date')]
        #[Assert\Date]
        public ?string $referenceDate = null,
        #[Assert\Positive]
        public int $page = 1,
        #[SerializedName('per_page')]
        #[Assert\Range(min: 1, max: 100)]
        public int $perPage = 20,
        #[Assert\Choice(choices: ['due_date', '-due_date', 'created_at', '-created_at', 'open_amount', '-open_amount'])]
        public string $sort = 'due_date',
    ) {
    }
}
