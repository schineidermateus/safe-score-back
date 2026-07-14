<?php

declare(strict_types=1);

namespace App\Identity\Application\Context;

use App\Identity\Domain\Entity\User;

interface CurrentUserProviderInterface
{
    public function currentUser(): User;
}
