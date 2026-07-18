<?php

declare(strict_types=1);

namespace App\Receivables\Application\DTO;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateReceivableInput
{
    public function __construct(
        #[SerializedName('document_number')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $documentNumber,
        #[SerializedName('issue_date')]
        #[Assert\Date]
        public string $issueDate,
        #[SerializedName('due_date')]
        #[Assert\Date]
        public string $dueDate,
        #[SerializedName('original_amount')]
        #[Assert\Regex(pattern: '/^(?:0|[1-9]\d{0,16})(?:\.\d{1,2})?$/')]
        public string $originalAmount,
    ) {
    }
}
