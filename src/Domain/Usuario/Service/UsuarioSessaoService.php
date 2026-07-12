<?php

namespace App\Domain\Usuario\Service;

use App\Domain\Usuario\Entity\Usuario;
use App\Domain\Usuario\Entity\UsuarioSessao;
use App\Domain\Usuario\Repository\UsuarioSessaoRepository;
use App\Infrastructure\Security\TokenService;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Uid\Uuid;

class UsuarioSessaoService
{
    public function __construct(
        private UsuarioSessaoRepository $repository,
        private TokenService $tokeService,
        private int $refreshTokenTtlHoras = 24 * 30 // 30 dias padrão
    ) {}

    /**
     * Cria uma nova sessão de usuário (login bem sucedido).
     */
    public function criarSessao(Usuario $usuario, string $userAgent = '', string $ip = ''): UsuarioSessao
    {
        // Evita dispositivos gerando milhares de sessões antigas
        $this->repository->limparExpiradas();

        $expiraEm = new \DateTimeImmutable("+{$this->refreshTokenTtlHoras} hours");

        $sessao = new UsuarioSessao(
            usuario: $usuario,
            refreshToken: Uuid::v4()->toRfc4122(),
            expiraEm: $expiraEm,
            ipAddress: $ip,
            userAgent: $userAgent
        );

        $this->repository->save($sessao);

        return $sessao;
    }

    /**
     * Valida se o refresh token ainda é válido.
     * Retorna a sessão ou lança exceção.
     */
    public function validarRefreshToken(string $refreshToken): UsuarioSessao
    {
        $sessao = $this->repository->buscarPorRefreshToken($refreshToken);

        if (!$sessao) {
            throw new \RuntimeException("Refresh token inválido ou revogado.");
        }

        if ($sessao->estaExpirado()) {
            throw new \RuntimeException("Refresh token expirado.");
        }

        return $sessao;
    }

    /**
     * Renova a sessão (gera novo refresh token).
     * Fluxo seguro de rotate-refresh-token.
     */
    public function renovarSessao(UsuarioSessao $sessao): UsuarioSessao
    {
        if ($sessao->revogado()) {
            throw new \RuntimeException("Sessão revogada.");
        }

        if ($sessao->estaExpirado()) {
            throw new \RuntimeException("Refresh token expirado.");
        }

        // Refresh-token rotation (OWASP recomendado)
        $novoToken = Uuid::v4()->toRfc4122();
        $novaExpiracao = new \DateTimeImmutable("+{$this->refreshTokenTtlHoras} hours");

        $sessao->renovar($novoToken, $novaExpiracao);

        $this->repository->save($sessao);

        return $sessao;
    }

    public function validarToken(string $token): UsuarioSessao
    {
        $tokenSerialized = $this->tokeService->validateToken($token);

        if (!$tokenSerialized) {
            throw new AuthenticationException('Invalid token.');
        }

        $sessao = $this->repository->buscarPorId($tokenSerialized['sid']);

        if ($sessao->isRevogado()) {
            throw new \RuntimeException("Token foi revogado.");
        }

        $agora = new \DateTimeImmutable();

        if ($sessao->getDataExpiracao() <= $agora) {
            throw new \RuntimeException("Token expirado.");
        }

        return $sessao;
    }

    /**
     * Atualiza último uso da sessão.
     */
    public function atualizarUltimoUso(UsuarioSessao $sessao): void
    {
        $this->repository->atualizarUltimoUso($sessao);
    }

    /**
     * Logout de apenas uma sessão.
     */
    public function revogarSessao(UsuarioSessao $sessao): void
    {
        $sessao->revogar();
        $this->repository->save($sessao);
    }

    /**
     * Logout global do usuário (revogar todas).
     */
    public function revogarTodas(Usuario $usuario): int
    {
        return $this->repository->revogarTodasPorUsuario($usuario);
    }

    /**
     * @return UsuarioSessao[]
     */
    public function buscarSessoesAtivas(Usuario $usuario): array
    {
        return $this->repository->buscarAtivasPorUsuario($usuario);
    }
}
