<?php

declare(strict_types=1);

namespace App\Organizations\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateMembershipInput
{
    public function __construct(
        #[Assert\Choice(choices: ['OWNER', 'ADMIN', 'ANALYST', 'VIEWER'])]
        public ?string $role = null,
        #[Assert\Choice(choices: ['SUSPENDED'])]
        public ?string $status = null,
    ) {
    }
}
