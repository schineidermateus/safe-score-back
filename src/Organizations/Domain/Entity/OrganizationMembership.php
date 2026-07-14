<?php

declare(strict_types=1);

namespace App\Organizations\Domain\Entity;

use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Organizations\Domain\Enum\MembershipStatus;
use App\Organizations\Infrastructure\Persistence\Doctrine\DoctrineOrganizationMembershipRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineOrganizationMembershipRepository::class)]
#[ORM\Table(name: 'organization_membership')]
#[ORM\UniqueConstraint(name: 'uniq_membership_organization_user', columns: ['organization_id', 'user_id'])]
#[ORM\Index(name: 'idx_membership_organization_status', columns: ['organization_id', 'status'])]
class OrganizationMembership
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', nullable: false, onDelete: 'CASCADE', options: ['unsigned' => true])]
    private Organization $organization;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE', options: ['unsigned' => true])]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: MembershipRole::class)]
    private MembershipRole $role;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: MembershipStatus::class)]
    private MembershipStatus $status;

    #[ORM\Column(name: 'joined_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $joinedAt;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    private function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    public static function join(
        Organization $organization,
        User $user,
        MembershipRole $role,
        \DateTimeImmutable $now,
    ): self {
        $membership = new self();
        $membership->organization = $organization;
        $membership->user = $user;
        $membership->role = $role;
        $membership->status = MembershipStatus::Active;
        $membership->joinedAt = $now;
        $membership->createdAt = $now;
        $membership->updatedAt = $now;

        return $membership;
    }

    public function changeRole(MembershipRole $role, \DateTimeImmutable $now): void
    {
        $this->role = $role;
        $this->updatedAt = $now;
    }

    public function suspend(\DateTimeImmutable $now): void
    {
        $this->status = MembershipStatus::Suspended;
        $this->updatedAt = $now;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        return $this->id ?? throw new \LogicException('Membership has not been persisted yet.');
    }

    public function organization(): Organization
    {
        return $this->organization;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function role(): MembershipRole
    {
        return $this->role;
    }

    public function status(): MembershipStatus
    {
        return $this->status;
    }

    public function grantsAccess(): bool
    {
        return MembershipStatus::Active === $this->status;
    }

    public function joinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
