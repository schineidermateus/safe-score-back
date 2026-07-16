<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security\Jwt;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
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

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly string $jwksUri,
        private readonly int $cacheTtl = 3600,
    ) {
    }

    public function publicKeyForKid(string $kid): string
    {
        $keys = $this->loadKeys(false);

        if (!isset($keys[$kid])) {
            // Possível rotação de chave: descarta o cache e tenta mais uma vez com um conjunto novo.
            $keys = $this->loadKeys(true);
        }

        if (!isset($keys[$kid])) {
            throw new JwtValidationException(sprintf('Nenhuma chave do JWKS corresponde ao key id "%s" do token.', $kid));
        }

        return JwkToPem::convert($keys[$kid]);
    }

    /**
     * @return array<string, array<string, mixed>> indexado por kid
     */
    private function loadKeys(bool $forceRefresh): array
    {
        if ($forceRefresh) {
            $this->cache->delete(self::CACHE_KEY);
        }

        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
            $item->expiresAfter($this->cacheTtl);

            return $this->fetchKeys();
        });
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fetchKeys(): array
    {
        try {
            $document = $this->httpClient
                ->request('GET', $this->jwksUri)
                ->toArray();
        } catch (\Throwable $exception) {
            throw new JwtValidationException('Não foi possível obter o documento JWKS.', 0, $exception);
        }

        $keys = [];
        foreach ($document['keys'] ?? [] as $key) {
            if (is_array($key) && 'RSA' === ($key['kty'] ?? null) && isset($key['kid'])) {
                $keys[(string) $key['kid']] = $key;
            }
        }

        return $keys;
    }
}
