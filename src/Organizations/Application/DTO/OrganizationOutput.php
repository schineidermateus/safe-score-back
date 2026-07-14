<?php

declare(strict_types=1);

namespace App\Organizations\Application\DTO;

use App\Organizations\Domain\Entity\Organization;

final readonly class OrganizationOutput
{
    public function __construct(
        public int $id,
        public string $legalName,
        public ?string $tradeName,
        public ?string $document,
        public string $status,
        public string $timezone,
        public string $currency,
    ) {
    }

    public static function fromEntity(Organization $organization): self
    {
        return new self(
            $organization->requireId(),
            $organization->legalName(),
            $organization->tradeName(),
            $organization->document(),
            $organization->status()->value,
            $organization->timezone(),
            $organization->currency(),
        );
    }

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'legal_name' => $this->legalName,
            'trade_name' => $this->tradeName,
            'document' => $this->document,
            'status' => $this->status,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
        ];
    }
}
