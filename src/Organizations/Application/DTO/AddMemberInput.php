<?php

declare(strict_types=1);

namespace App\Organizations\Application\DTO;

use App\Organizations\Domain\Enum\MembershipRole;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AddMemberInput
{
    public function __construct(
        #[SerializedName('user_id')]
        #[Assert\Positive]
        public int $userId,
        #[Assert\Choice(callback: [MembershipRole::class, 'values'])]
        public string $role,
    ) {
    }
}
