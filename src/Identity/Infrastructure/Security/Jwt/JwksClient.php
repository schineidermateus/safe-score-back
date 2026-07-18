<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security\Jwt;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Busca e cacheia o JSON Web Key Set do serviço de autenticação.
 *
 * Apenas as chaves públicas são obtidas, então este componente nunca consegue
 * emitir tokens. O conjunto é cacheado; um "kid" desconhecido dispara um único
 * refresh forçado, para que a rotação de chaves no serviço de auth seja
 * absorvida sem redeploy.
 */
final class JwksClient
{
    private const CACHE_KEY = 'safescore.jwks.keys';
    private const REFRESH_GUARD_KEY = 'safescore.jwks.refresh_guard';
    private const MAX_DOCUMENT_BYTES = 1048576;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
        private readonly string $jwksUri,
        private readonly int $cacheTtl = 3600,
        private readonly int $refreshInterval = 30,
    ) {
        if ('https' !== parse_url($this->jwksUri, \PHP_URL_SCHEME)) {
            throw new \InvalidArgumentException('JWKS_URI deve utilizar HTTPS em produção.');
        }
        if ($this->cacheTtl < 1 || $this->refreshInterval < 1) {
            throw new \InvalidArgumentException('Os intervalos de cache do JWKS devem ser positivos.');
        }
    }

    public function publicKeyForKid(string $kid): string
    {
        $cached = $this->loadKeys();
        $keys = $cached['keys'];

        if (!isset($keys[$kid]) && time() - $cached['fetched_at'] >= $this->refreshInterval) {
            $keys = $this->refreshKeys();
        }

        if (!isset($keys[$kid])) {
            throw new JwtValidationException(sprintf('Nenhuma chave do JWKS corresponde ao key id "%s" do token.', $kid));
        }

        return JwkToPem::convert($keys[$kid]);
    }

    /**
     * @return array{keys: array<string, array<string, mixed>>, fetched_at: int}
     */
    private function loadKeys(): array
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        $cached = $item->get();
        if ($item->isHit() && $this->isCachedDocument($cached)) {
            return $cached;
        }

        $document = ['keys' => $this->fetchKeys(), 'fetched_at' => time()];
        $item->set($document);
        $item->expiresAfter($this->cacheTtl);
        $this->cache->save($item);

        return $document;
    }

    /** @return array<string, array<string, mixed>> */
    private function refreshKeys(): array
    {
        $guard = $this->cache->getItem(self::REFRESH_GUARD_KEY);
        if ($guard->isHit()) {
            return $this->loadKeys()['keys'];
        }

        $guard->set(true);
        $guard->expiresAfter($this->refreshInterval);
        $this->cache->save($guard);

        $keys = $this->fetchKeys();
        $item = $this->cache->getItem(self::CACHE_KEY);
        $item->set(['keys' => $keys, 'fetched_at' => time()]);
        $item->expiresAfter($this->cacheTtl);
        $this->cache->save($item);

        return $keys;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fetchKeys(): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->jwksUri, [
                'headers' => ['Accept' => 'application/json'],
                'max_redirects' => 0,
                'timeout' => 3.0,
                'max_duration' => 5.0,
            ]);
            $content = $response->getContent();
            if (strlen($content) > self::MAX_DOCUMENT_BYTES) {
                throw new JwtValidationException('O documento JWKS excede o tamanho máximo permitido.');
            }
            $document = json_decode($content, true, 64, \JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            if ($exception instanceof JwtValidationException) {
                throw $exception;
            }
            throw new JwtValidationException('Não foi possível obter o documento JWKS.', 0, $exception);
        }

        if (!is_array($document) || !isset($document['keys']) || !is_array($document['keys']) || !array_is_list($document['keys'])) {
            throw new JwtValidationException('O documento JWKS não contém uma lista de chaves válida.');
        }

        $keys = [];
        foreach ($document['keys'] as $key) {
            if (!is_array($key) || !$this->isVerificationKey($key)) {
                continue;
            }
            $kid = $key['kid'];
            if (isset($keys[$kid])) {
                throw new JwtValidationException(sprintf('O documento JWKS contém o kid duplicado "%s".', $kid));
            }
            $keys[$kid] = $key;
        }
        if ([] === $keys) {
            throw new JwtValidationException('O documento JWKS não contém chaves RSA de assinatura compatíveis com RS256.');
        }

        return $keys;
    }

    /** @param array<string, mixed> $key */
    private function isVerificationKey(array $key): bool
    {
        if ('RSA' !== ($key['kty'] ?? null)
            || !isset($key['kid'], $key['n'], $key['e'])
            || !is_string($key['kid']) || '' === $key['kid']
            || !is_string($key['n']) || '' === $key['n']
            || !is_string($key['e']) || '' === $key['e']) {
            return false;
        }
        if (isset($key['use']) && 'sig' !== $key['use']) {
            return false;
        }
        if (isset($key['alg']) && 'RS256' !== $key['alg']) {
            return false;
        }
        if (isset($key['key_ops']) && (!is_array($key['key_ops']) || !in_array('verify', $key['key_ops'], true))) {
            return false;
        }

        return true;
    }

    private function isCachedDocument(mixed $value): bool
    {
        return is_array($value)
            && isset($value['keys'], $value['fetched_at'])
            && is_array($value['keys'])
            && is_int($value['fetched_at']);
    }
}
