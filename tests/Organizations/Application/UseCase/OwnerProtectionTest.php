<?php

declare(strict_types=1);

namespace App\Tests\Organizations\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Identity\Domain\Entity\User;
use App\Organizations\Application\UseCase\ChangeMembershipRole;
use App\Organizations\Application\UseCase\SuspendMembership;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Shared\Domain\Exception\DomainException;
use App\Tests\Organizations\Support\InMemoryMembershipRepository;
use App\Tests\Support\CurrentContextStub;
use App\Tests\Support\EntityId;
use App\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class OwnerProtectionTest extends TestCase
{
    public function testOneOfTwoOwnersCanBeDemotedAfterOwnerRowsAreLocked(): void
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization', null, null, $now);
        $currentUser = User::create('Current Owner', 'current-owner@example.com', $now);
        $targetUser = User::create('Target Owner', 'target-owner@example.com', $now);
        EntityId::assign($organization, 1);
        EntityId::assign($currentUser, 1);
        EntityId::assign($targetUser, 2);
        $currentMembership = OrganizationMembership::join($organization, $currentUser, MembershipRole::Owner, $now);
        $targetMembership = OrganizationMembership::join($organization, $targetUser, MembershipRole::Owner, $now);
        $repository = new InMemoryMembershipRepository();
        $repository->save($currentMembership);
        $repository->save($targetMembership);
        $context = new CurrentContextStub($currentUser, $organization, $currentMembership);

        (new ChangeMembershipRole($repository, $context, new AuthorizationService($context), new ImmediateTransactionManager()))
            ->execute($targetMembership->requireId(), MembershipRole::Admin);

        self::assertSame(MembershipRole::Admin, $targetMembership->role());
        self::assertSame(1, $repository->countActiveOwners($organization));
        self::assertSame(1, $repository->activeOwnerLockCount());
    }

    public function testLastOwnerCannotBeDemoted(): void
    {
        [$membership, $repository, $context] = $this->fixture();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A organização deve manter ao menos um OWNER ativo.');

        (new ChangeMembershipRole($repository, $context, new AuthorizationService($context), new ImmediateTransactionManager()))
            ->execute($membership->requireId(), MembershipRole::Admin);
    }

    public function testLastOwnerCannotBeSuspended(): void
    {
        [$membership, $repository, $context] = $this->fixture();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A organização deve manter ao menos um OWNER ativo.');

        (new SuspendMembership($repository, $context, new AuthorizationService($context), new ImmediateTransactionManager()))
            ->execute($membership->requireId());
    }

    /** @return array{OrganizationMembership, InMemoryMembershipRepository, CurrentContextStub} */
    private function fixture(): array
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization', null, null, $now);
        $user = User::create('Owner', 'owner@example.com', $now);
        EntityId::assign($organization, 1);
        EntityId::assign($user, 1);
        $membership = OrganizationMembership::join($organization, $user, MembershipRole::Owner, $now);
        $repository = new InMemoryMembershipRepository();
        $repository->save($membership);
        $context = new CurrentContextStub($user, $organization, $membership);

        return [$membership, $repository, $context];
    }
}
