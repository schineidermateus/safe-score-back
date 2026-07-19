<?php

declare(strict_types=1);

namespace App\Organizations\Application\DTO;

use App\Organizations\Domain\Entity\OrganizationMembership;

final readonly class AccessibleOrganizationOutput
{
    private function __construct(private OrganizationMembership $membership)
    {
    }

    public static function fromMembership(OrganizationMembership $membership): self
    {
        return new self($membership);
    }

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        $organization = $this->membership->organization();

        return [
            'id' => $organization->requireId(),
            'legal_name' => $organization->legalName(),
            'trade_name' => $organization->tradeName(),
            'status' => $organization->status()->value,
            'membership_id' => $this->membership->requireId(),
            'membership_role' => $this->membership->role()->value,
        ];
    }
}
