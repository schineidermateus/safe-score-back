<?php

declare(strict_types=1);

namespace App\Customers\Application\DTO;

use App\Customers\Domain\Entity\Customer;

final readonly class CustomerOutput
{
    public function __construct(
        public string $id,
        public ?string $externalId,
        public string $legalName,
        public ?string $tradeName,
        public ?string $document,
        public ?string $segment,
        public string $status,
        public ?string $accountManager,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Customer $customer): self
    {
        return new self(
            $customer->id(),
            $customer->externalId(),
            $customer->legalName(),
            $customer->tradeName(),
            $customer->document(),
            $customer->segment(),
            $customer->status()->value,
            $customer->accountManager(),
            $customer->createdAt()->format(\DateTimeInterface::ATOM),
            $customer->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->externalId,
            'legal_name' => $this->legalName,
            'trade_name' => $this->tradeName,
            'document' => $this->document,
            'segment' => $this->segment,
            'status' => $this->status,
            'account_manager' => $this->accountManager,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
