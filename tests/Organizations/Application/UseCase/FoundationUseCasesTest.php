<?php

declare(strict_types=1);

namespace App\Tests\Organizations\Application\UseCase;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Authorization\Domain\Entity\Capability;
use App\Authorization\Domain\Entity\Role;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Identity\Application\DTO\CreateUserInput;
use App\Identity\Application\UseCase\CreateUser;
use App\Identity\Domain\Entity\User;
use App\Organizations\Application\DTO\AddMemberInput;
use App\Organizations\Application\DTO\CreateOrganizationInput;
use App\Organizations\Application\UseCase\AddUserToOrganization;
use App\Organizations\Application\UseCase\CreateOrganization;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Shared\Domain\Exception\DomainException;
use App\Tests\Audit\Support\InMemoryAuditLogRepository;
use App\Tests\Identity\Support\InMemoryUserRepository;
use App\Tests\Organizations\Support\InMemoryMembershipRepository;
use App\Tests\Organizations\Support\InMemoryOrganizationRepository;
use App\Tests\Support\CurrentContextStub;
use App\Tests\Support\EntityId;
use App\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class FoundationUseCasesTest extends TestCase
{
    public function testUserEmailMustBeUniqueAfterNormalization(): void
    {
        $useCase = new CreateUser(new InMemoryUserRepository());
        $useCase->execute(new CreateUserInput('First', 'user@example.com'));

        $this->expectException(DomainException::class);
        $useCase->execute(new CreateUserInput('Second', ' USER@EXAMPLE.COM '));
    }

    public function testOrganizationDocumentIsUniqueAndInitialOwnerIsRegistered(): void
    {
        $now = new \DateTimeImmutable();
        $owner = User::create('Owner', 'owner@example.com', $now);
        EntityId::assign($owner, 1);
        $organizations = new InMemoryOrganizationRepository();
        $memberships = new InMemoryMembershipRepository();
        $currentUser = new class($owner) implements CurrentUserProviderInterface {
            public function __construct(private User $user)
            {
            }

            public function currentUser(): User
            {
                return $this->user;
            }
        };
        $useCase = new CreateOrganization($organizations, $memberships, $currentUser, new ImmediateTransactionManager(), new AuditLogger(new InMemoryAuditLogRepository()));
        $useCase->execute(new CreateOrganizationInput('Stone Organization A', document: '04.252.011/0001-10'));

        $created = $organizations->findById(1);
        self::assertNotNull($created);
        $membership = $memberships->findByOrganizationAndUser($created, $owner);
        self::assertSame(MembershipRole::Owner, $membership?->role());

        $this->expectException(DomainException::class);
        $useCase->execute(new CreateOrganizationInput('Stone Organization B', document: '04252011000110'));
    }

    public function testDuplicateMembershipIsRejectedAndSuspendedMembershipDoesNotGrantAccess(): void
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization', null, null, $now);
        $owner = User::create('Owner', 'owner@example.com', $now);
        $target = User::create('Target', 'target@example.com', $now);
        EntityId::assign($organization, 1);
        EntityId::assign($owner, 1);
        EntityId::assign($target, 2);

        $ownerMembership = OrganizationMembership::join($organization, $owner, MembershipRole::Owner, $now);
        $role = Role::create('TEST_ORG_MANAGER', 'Test organization manager');
        foreach ([AuthorizationAction::ManageMembers, AuthorizationAction::AssignOwner] as $action) {
            $role->grant(Capability::create($action->value, 'Test grant'));
        }
        $ownerMembership->assignAuthorizationRole($role);
        $duplicate = OrganizationMembership::join($organization, $target, MembershipRole::Viewer, $now);
        $memberships = new InMemoryMembershipRepository();
        $memberships->save($ownerMembership);
        $memberships->save($duplicate);
        $users = new InMemoryUserRepository();
        $users->save($target);
        $context = new CurrentContextStub($owner, $organization, $ownerMembership);

        $duplicate->suspend($now);
        self::assertFalse($duplicate->grantsAccess());

        $this->expectException(DomainException::class);
        (new AddUserToOrganization($users, $memberships, $context, $context, new AuthorizationService($context), new AuditLogger(new InMemoryAuditLogRepository()), new ImmediateTransactionManager()))
            ->execute(new AddMemberInput($target->requireId(), MembershipRole::Viewer->value));
    }
}
