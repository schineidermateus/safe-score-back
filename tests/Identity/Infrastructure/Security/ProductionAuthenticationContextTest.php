<?php

declare(strict_types=1);

namespace App\Tests\Identity\Infrastructure\Security;

use App\Identity\Application\Context\AuthenticatedToken;
use App\Identity\Application\Context\AuthenticatedTokenProviderInterface;
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
use App\Tests\Identity\Support\InMemoryUserRepository;
use App\Tests\Identity\Support\JwtTestFactory;
use App\Tests\Organizations\Support\InMemoryOrganizationRepository;
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
        $users = new InMemoryUserRepository();
        $user = User::create('User', 'local-profile@example.com', $now);
        $user->linkExternalIdentity('https://auth.safescore.local', 'user:123', $now);
        $users->save($user);
        $context = new RequestAuthenticatedTokenProvider();
        $handler = new JwtAccessTokenHandler($this->validator($factory), $context, $users);

        $badge = $handler->getUserBadgeFrom($factory->token($factory->claims()));

        self::assertSame($user, $badge->getUser());
        self::assertSame('user:123', $badge->getUserIdentifier());
        self::assertSame('user@example.com', $context->current()->email);
    }

    public function testInactiveUserIsRejected(): void
    {
        $user = User::create('User', 'user@example.com', new \DateTimeImmutable());
        (new \ReflectionProperty(User::class, 'status'))->setValue($user, UserStatus::Suspended);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        (new ProductionUserChecker())->checkPreAuth($user);
    }

    public function testInactiveOrganizationIsRejectedEvenWithSignedTenantClaim(): void
    {
        $organizations = new InMemoryOrganizationRepository();
        $organization = Organization::create('Organization', null, null, new \DateTimeImmutable());
        $organizations->save($organization);
        (new \ReflectionProperty(Organization::class, 'status'))->setValue($organization, OrganizationStatus::Suspended);
        $token = new AuthenticatedToken(
            'https://auth.safescore.local',
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
        $this->expectExceptionMessage('A organização atual não está ativa.');
        (new TokenCurrentOrganizationProvider($organizations, $tokenProvider))->currentOrganization();
    }

    private function validator(JwtTestFactory $factory): JwtTokenValidator
    {
        $http = new MockHttpClient([
            new MockResponse(json_encode(['keys' => [$factory->jwk()]], \JSON_THROW_ON_ERROR)),
        ]);

        return new JwtTokenValidator(
            new JwksClient($http, new ArrayAdapter(), 'https://auth.safescore.local/jwks'),
            'https://auth.safescore.local',
            'safescore-api',
        );
    }
}
