<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entity;

use App\Identity\Domain\Enum\ExternalIdentityStatus;
use App\Identity\Infrastructure\Persistence\Doctrine\DoctrineExternalIdentityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineExternalIdentityRepository::class)]
#[ORM\Table(name: 'external_identity')]
#[ORM\UniqueConstraint(name: 'uniq_external_identity_issuer_subject', columns: ['issuer', 'subject'])]
#[ORM\Index(name: 'idx_external_identity_user_status', columns: ['user_id', 'status'])]
class ExternalIdentity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE', options: ['unsigned' => true])]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['collation' => 'utf8mb4_bin'])]
    private string $issuer;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['collation' => 'utf8mb4_bin'])]
    private string $subject;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: ExternalIdentityStatus::class)]
    private ExternalIdentityStatus $status;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    private function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    public static function link(User $user, string $issuer, string $subject, \DateTimeImmutable $now): self
    {
        $identity = new self();
        $identity->user = $user;
        $identity->issuer = self::required($issuer, 'Issuer');
        $identity->subject = self::required($subject, 'Subject');
        $identity->status = ExternalIdentityStatus::Active;
        $identity->createdAt = $now;
        $identity->updatedAt = $now;

        return $identity;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        return $this->id ?? throw new \LogicException('External identity has not been persisted yet.');
    }

    public function user(): User
    {
        return $this->user;
    }

    public function issuer(): string
    {
        return $this->issuer;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function isActive(): bool
    {
        return ExternalIdentityStatus::Active === $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function suspend(\DateTimeImmutable $now): void
    {
        $this->status = ExternalIdentityStatus::Suspended;
        $this->updatedAt = $now;
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
