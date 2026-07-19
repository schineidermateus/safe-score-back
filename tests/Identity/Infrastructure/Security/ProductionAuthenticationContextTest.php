<?php

declare(strict_types=1);

namespace App\Tests\Identity\Infrastructure\Security;

use App\Identity\Application\Context\AuthenticatedToken;
use App\Identity\Application\Context\AuthenticatedTokenProviderInterface;
use App\Identity\Domain\Entity\ExternalIdentity;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Enum\UserStatus;
use App\Identity\Infrastructure\Security\Jwt\JwksClient;
use App\Identity\Infrastructure\Security\Jwt\JwtTokenValidator;
use App\Identity\Infrastructure\Security\JwtAccessTokenHandler;
use App\Identity\Infrastructure\Security\ProductionUserChecker;
use App\Identity\Infrastructure\Security\RequestAuthenticatedTokenProvider;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Enum\OrganizationStatus;
use App\Organizations\Infrastructure\Context\TokenCurrentOrganizationProvider;
use App\Shared\Domain\Exception\DomainException;
use App\Tests\Identity\Support\InMemoryExternalIdentityRepository;
use App\Tests\Identity\Support\JwtTestFactory;
use App\Tests\Organizations\Support\InMemoryMembershipRepository;
use App\Tests\Organizations\Support\InMemoryOrganizationRepository;
use App\Tests\Support\CurrentContextStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class ProductionAuthenticationContextTest extends TestCase
{
    public function testHandlerResolvesUserByIssuerAndSubjectInsteadOfEmail(): void
    {
        $now = new \DateTimeImmutable();
        $factory = new JwtTestFactory();
        $identities = new InMemoryExternalIdentityRepository();
        $user = User::create('User', 'local-profile@example.com', $now);
        $identities->save(ExternalIdentity::link($user, 'https://auth.stone.local', 'user:123', $now));
        $context = new RequestAuthenticatedTokenProvider();
        $handler = new JwtAccessTokenHandler($this->validator($factory), $context, $identities);

        $badge = $handler->getUserBadgeFrom($factory->token($factory->claims()));

        self::assertSame($user, $badge->getUser());
        self::assertSame('user:123', $badge->getUserIdentifier());
        self::assertSame('user@example.com', $context->current()->email);
    }

    public function testHandlerIgnoresNumericUserIdAndUsesExternalSubject(): void
    {
        $factory = new JwtTestFactory();
        $identities = new InMemoryExternalIdentityRepository();
        $user = User::create('External User', 'external@example.com', new \DateTimeImmutable());
        $identities->save(ExternalIdentity::link($user, 'https://auth.stone.local', 'user:123', new \DateTimeImmutable()));
        $context = new RequestAuthenticatedTokenProvider();
        $handler = new JwtAccessTokenHandler($this->validator($factory), $context, $identities);

        $claims = array_replace($factory->claims(), ['user_id' => 999]);
        self::assertSame($user, $handler->getUserBadgeFrom($factory->token($claims))->getUser());
        self::assertSame('user:123', $context->current()->subject);
    }

    public function testInactiveUserIsRejected(): void
    {
        $user = User::create('User', 'user@example.com', new \DateTimeImmutable());
        (new \ReflectionProperty(User::class, 'status'))->setValue($user, UserStatus::Suspended);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        (new ProductionUserChecker())->checkPreAuth($user);
    }

    public function testSuspendedExternalIdentityCannotResolveAUser(): void
    {
        $now = new \DateTimeImmutable();
        $factory = new JwtTestFactory();
        $identity = ExternalIdentity::link(User::create('User', 'user@example.com', $now), 'https://auth.stone.local', 'user:123', $now);
        $identity->suspend($now);
        $identities = new InMemoryExternalIdentityRepository();
        $identities->save($identity);
        $badge = (new JwtAccessTokenHandler($this->validator($factory), new RequestAuthenticatedTokenProvider(), $identities))
            ->getUserBadgeFrom($factory->token($factory->claims()));

        $this->expectException(\Symfony\Component\Security\Core\Exception\UserNotFoundException::class);
        $badge->getUser();
    }

    public function testTenantWithoutClaimIsResolvedOnlyWhenMembershipIsUnambiguous(): void
    {
        $now = new \DateTimeImmutable();
        $organizations = new InMemoryOrganizationRepository();
        $memberships = new InMemoryMembershipRepository();
        $user = User::create('User', 'user@example.com', $now);
        $alpha = Organization::create('Alpha', null, null, $now);
        $beta = Organization::create('Beta', null, null, $now);
        $organizations->save($alpha);
        $organizations->save($beta);
        $alphaMembership = \App\Organizations\Domain\Entity\OrganizationMembership::join($alpha, $user, \App\Organizations\Domain\Enum\MembershipRole::Viewer, $now);
        $memberships->save($alphaMembership);
        $context = new CurrentContextStub($user, $alpha, $alphaMembership);
        $tokenProvider = $this->tokenProvider(new AuthenticatedToken('https://auth.stone.local', 'user:123', null, null));
        $provider = new TokenCurrentOrganizationProvider($organizations, $tokenProvider, $context, $memberships);

        self::assertSame($alpha, $provider->currentOrganization());

        $memberships->save(\App\Organizations\Domain\Entity\OrganizationMembership::join($beta, $user, \App\Organizations\Domain\Enum\MembershipRole::Viewer, $now));
        try {
            $provider->currentOrganization();
            self::fail('Multiple memberships were resolved without an explicit organization context.');
        } catch (DomainException $exception) {
            self::assertSame('ORGANIZATION_SELECTION_REQUIRED', $exception->errorCode());
            self::assertSame(409, $exception->statusCode());
        }
    }

    public function testInactiveOrganizationIsRejectedEvenWithSignedTenantClaim(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $organization = Organization::create('Organization', null, null, new \DateTimeImmutable());
        $organizations->save($organization);
        (new \ReflectionProperty(Organization::class, 'status'))->setValue($organization, OrganizationStatus::Suspended);
        $token = new AuthenticatedToken(
            'https://auth.stone.local',
            'user:123',
            'user@example.com',
            $organization->requireId(),
        );
        $tokenProvider = new class($token) implements AuthenticatedTokenProviderInterface {
            public function __construct(private readonly AuthenticatedToken $token)
            {
            }

            public function current(): AuthenticatedToken
            {
                return $this->token;
            }
        };

        $this->expectException(DomainException::class);
        $user = User::create('User', 'user@example.com', new \DateTimeImmutable());
        $memberships = new InMemoryMembershipRepository();
        $membership = \App\Organizations\Domain\Entity\OrganizationMembership::join($organization, $user, \App\Organizations\Domain\Enum\MembershipRole::Viewer, new \DateTimeImmutable());
        $memberships->save($membership);
        $context = new CurrentContextStub($user, $organization, $membership);
        (new TokenCurrentOrganizationProvider($organizations, $tokenProvider, $context, $memberships))->currentOrganization();
    }

    private function validator(JwtTestFactory $factory): JwtTokenValidator
    {
        $http = new MockHttpClient([
            new MockResponse(json_encode(['keys' => [$factory->jwk()]], \JSON_THROW_ON_ERROR)),
        ]);

        return new JwtTokenValidator(
            new JwksClient($http, new ArrayAdapter(), 'https://auth.stone.local/jwks'),
            'https://auth.stone.local',
            'stone-traceability-api',
        );
    }

    private function tokenProvider(AuthenticatedToken $token): AuthenticatedTokenProviderInterface
    {
        return new class($token) implements AuthenticatedTokenProviderInterface {
            public function __construct(private readonly AuthenticatedToken $token)
            {
            }

            public function current(): AuthenticatedToken
            {
                return $this->token;
            }
        };
    }
}
