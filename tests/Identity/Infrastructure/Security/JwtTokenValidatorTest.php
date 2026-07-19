<?php

declare(strict_types=1);

namespace App\Tests\Identity\Infrastructure\Security;

use App\Identity\Infrastructure\Security\Jwt\JwksClient;
use App\Identity\Infrastructure\Security\Jwt\JwtTokenValidator;
use App\Identity\Infrastructure\Security\Jwt\JwtValidationException;
use App\Tests\Identity\Support\JwtTestFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class JwtTokenValidatorTest extends TestCase
{
    public function testItValidatesSignatureAndRequiredClaims(): void
    {
        $factory = new JwtTestFactory();
        $validator = $this->validator($factory);

        $token = $validator->validate($factory->token($factory->claims()));

        self::assertSame('https://auth.stone.local', $token->issuer);
        self::assertSame('user:123', $token->subject);
        self::assertSame('user@example.com', $token->email);
        self::assertSame(42, $token->organizationId);
    }

    public function testItRejectsInvalidClaimTypesAndValues(): void
    {
        $factory = new JwtTestFactory();
        $validator = $this->validator($factory);
        $invalidClaims = [
            ['exp' => (string) (time() + 300)],
            ['organization_id' => ['42']],
            ['organization_id' => 0],
            ['iat' => time() + 301],
            ['sub' => ''],
            ['email' => 'not-an-email'],
            ['aud' => [123]],
        ];

        foreach ($invalidClaims as $override) {
            try {
                $validator->validate($factory->token(array_replace($factory->claims(), $override)));
                self::fail('O token com claim inválido foi aceito: '.json_encode($override, \JSON_THROW_ON_ERROR));
            } catch (JwtValidationException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testEmailAndOrganizationAreOptionalForExternalIdentityAuthentication(): void
    {
        $factory = new JwtTestFactory();
        $claims = $factory->claims();
        unset($claims['email'], $claims['organization_id']);

        $token = $this->validator($factory)->validate($factory->token($claims));

        self::assertNull($token->email);
        self::assertNull($token->organizationId);
    }

    public function testAudienceArrayIsAcceptedAndIssuedAtIsRequired(): void
    {
        $factory = new JwtTestFactory();
        $claims = array_replace($factory->claims(), ['aud' => ['another-api', 'stone-traceability-api']]);
        self::assertSame('user:123', $this->validator($factory)->validate($factory->token($claims))->subject);

        unset($claims['iat']);
        $this->expectException(JwtValidationException::class);
        $this->validator($factory)->validate($factory->token($claims));
    }

    public function testItRejectsExpiredWrongIssuerAudienceAlgorithmAndSignature(): void
    {
        $factory = new JwtTestFactory();
        $validator = $this->validator($factory);
        $invalidTokens = [
            $factory->token(array_replace($factory->claims(), ['exp' => time() - 120])),
            $factory->token(array_replace($factory->claims(), ['iss' => 'https://attacker.invalid'])),
            $factory->token(array_replace($factory->claims(), ['aud' => 'another-api'])),
            $factory->token($factory->claims(), ['alg' => 'HS256']),
            (new JwtTestFactory())->token($factory->claims()),
        ];

        foreach ($invalidTokens as $token) {
            try {
                $validator->validate($token);
                self::fail('Um JWT inválido foi aceito.');
            } catch (JwtValidationException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testJwksRequiresHttps(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new JwksClient(new MockHttpClient(), new ArrayAdapter(), 'http://auth.stone.local/jwks');
    }

    public function testJwksRejectsKeysThatCannotVerifyRs256Signatures(): void
    {
        $factory = new JwtTestFactory();
        $invalidKeys = [
            array_replace($factory->jwk('encryption'), ['use' => 'enc']),
            array_replace($factory->jwk('wrong-algorithm'), ['alg' => 'RS512']),
            array_replace($factory->jwk('wrong-operation'), ['key_ops' => ['sign']]),
        ];
        $http = new MockHttpClient([
            new MockResponse(json_encode(['keys' => $invalidKeys], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new JwksClient($http, new ArrayAdapter(), 'https://auth.stone.local/jwks');

        $this->expectException(JwtValidationException::class);
        $client->publicKeyForKid('encryption');
    }

    public function testJwksRejectsDuplicateKeyIdentifiers(): void
    {
        $factory = new JwtTestFactory();
        $http = new MockHttpClient([
            new MockResponse(json_encode([
                'keys' => [$factory->jwk('duplicate'), $factory->jwk('duplicate')],
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new JwksClient($http, new ArrayAdapter(), 'https://auth.stone.local/jwks');

        $this->expectException(JwtValidationException::class);
        $client->publicKeyForKid('duplicate');
    }

    public function testJwksRejectsMalformedRsaParameters(): void
    {
        $factory = new JwtTestFactory();
        $malformed = array_replace($factory->jwk('malformed'), ['n' => 'not+base64url']);
        $http = new MockHttpClient([
            new MockResponse(json_encode(['keys' => [$malformed]], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new JwksClient($http, new ArrayAdapter(), 'https://auth.stone.local/jwks');

        $this->expectException(JwtValidationException::class);
        $client->publicKeyForKid('malformed');
    }

    public function testValidatorRejectsInvalidConfiguration(): void
    {
        $factory = new JwtTestFactory();
        $jwks = new JwksClient(new MockHttpClient(), new ArrayAdapter(), 'https://auth.stone.local/jwks');

        $this->expectException(\InvalidArgumentException::class);
        new JwtTokenValidator($jwks, '', 'stone-traceability-api', -1);
    }

    public function testUnknownKidRefreshIsThrottledWithoutDeletingValidKeys(): void
    {
        $old = new JwtTestFactory();
        $rotated = new JwtTestFactory();
        $cache = new ArrayAdapter();
        $cached = $cache->getItem('stone_traceability.jwks.keys');
        $cached->set(['keys' => ['old' => $old->jwk('old')], 'fetched_at' => time() - 120]);
        $cached->expiresAfter(3600);
        $cache->save($cached);
        $http = new MockHttpClient([
            new MockResponse(json_encode(['keys' => [$rotated->jwk('new')]], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new JwksClient($http, $cache, 'https://auth.stone.local/jwks', 3600, 30);

        self::assertStringContainsString('BEGIN PUBLIC KEY', $client->publicKeyForKid('new'));

        $cached = $cache->getItem('stone_traceability.jwks.keys');
        $cached->set(['keys' => ['new' => $rotated->jwk('new')], 'fetched_at' => time() - 120]);
        $cache->save($cached);
        try {
            $client->publicKeyForKid('attacker-controlled-kid');
            self::fail('Um kid inexistente foi aceito.');
        } catch (JwtValidationException) {
            self::assertSame(1, $http->getRequestsCount());
        }
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
            30,
        );
    }
}
