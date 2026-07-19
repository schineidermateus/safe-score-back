<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entity;

use App\Identity\Domain\Enum\UserStatus;
use App\Identity\Infrastructure\Persistence\Doctrine\DoctrineUserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: DoctrineUserRepository::class)]
#[ORM\Table(name: 'app_user')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email'])]
#[ORM\Index(name: 'idx_user_status', columns: ['status'])]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 120)]
    private string $name;

    /** @var non-empty-string */
    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $email;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: UserStatus::class)]
    private UserStatus $status;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    private function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    public static function create(string $name, string $email, \DateTimeImmutable $now): self
    {
        $user = new self();
        $user->name = self::required($name, 'Name');
        $user->email = self::normalizeEmail($email);
        $user->status = UserStatus::Active;
        $user->createdAt = $now;
        $user->updatedAt = $now;

        return $user;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        return $this->id ?? throw new \LogicException('User has not been persisted yet.');
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return UserStatus::Active === $this->status;
    }

    public function suspend(\DateTimeImmutable $now): void
    {
        $this->status = UserStatus::Suspended;
        $this->updatedAt = $now;
    }

    public function deactivate(\DateTimeImmutable $now): void
    {
        $this->status = UserStatus::Inactive;
        $this->updatedAt = $now;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    /** @return non-empty-string */
    private static function normalizeEmail(string $email): string
    {
        $email = mb_strtolower(trim($email));

        if ('' === $email || false === filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email.');
        }

        return $email;
    }

    private static function required(string $value, string $field): string
    {
        $value = trim($value);
        if ('' === $value) {
            throw new \InvalidArgumentException($field.' cannot be empty.');
        }

        return $value;
    }
}
