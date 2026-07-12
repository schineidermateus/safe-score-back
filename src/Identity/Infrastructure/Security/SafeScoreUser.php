<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\Security\AuthenticatedUser;
use App\Organizations\Domain\ValueObject\OrganizationId;

final readonly class SafeScoreUser implements AuthenticatedUser
{
    /**
     * @var non-empty-string
     */
    private string $identifier;

    /**
     * @param list<string> $roles
     */
    public function __construct(
        private string $id,
        string $identifier,
        private ?OrganizationId $organizationId,
        private array $roles = ['ROLE_USER'],
    ) {
        $identifier = trim($identifier);

        if ('' === $identifier) {
            throw new \InvalidArgumentException('The user identifier cannot be empty.');
        }

        $this->identifier = $identifier;
    }

    public function userId(): string
    {
        return $this->id;
    }

    public function activeOrganizationId(): ?OrganizationId
    {
        return $this->organizationId;
    }

    /**
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    public function eraseCredentials(): void
    {
    }
}
