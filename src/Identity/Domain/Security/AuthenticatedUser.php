<?php

declare(strict_types=1);

namespace App\Identity\Domain\Security;

use App\Organizations\Domain\ValueObject\OrganizationId;
use Symfony\Component\Security\Core\User\UserInterface;

interface AuthenticatedUser extends UserInterface
{
    public function userId(): string;

    public function activeOrganizationId(): ?OrganizationId;
}
