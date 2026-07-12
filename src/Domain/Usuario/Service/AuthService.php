<?php

namespace App\Domain\Usuario\Service;

use App\Domain\Usuario\Entity\Usuario;
use App\Domain\Usuario\Entity\UsuarioSessao;
use App\Domain\Usuario\Repository\UsuarioRepository;
use App\Http\DTO\Usuario\RegisterRequest;
use App\Infrastructure\Http\ApiResponse;
use App\Infrastructure\Security\TokenService;
use App\Shared\Interface\RedisInterface;
use Random\RandomException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AuthService
{
    private int $accessTokenTtlSeconds;

    private const EMAIL_TOKEN_START = 'email_token_start:';
    private const EMAIL_TOKEN_FINISH = 'email_token_finish:';

    public function __construct(
        private readonly RedisInterface $redis,
        private readonly UsuarioRepository $usuarioRepository,
        private readonly UsuarioSessaoService $sessaoService,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TokenService $tokenService,
        int $accessTokenTtlSeconds = 900 * 4, // 1 hora
    ) {
        $this->accessTokenTtlSeconds = $accessTokenTtlSeconds;
    }

    /**
     * Efetua login com email e senha.
     *
     * @return array { access_token: string, token_type: "bearer", expires_in: int, refresh_token: string, session_id: int }
     *
     * @throws AuthenticationException when credentials invalid
     */
    public function login(string $email, string $plainPassword, string $userAgent = '', string $ip = ''): array
    {
        $user = $this->usuarioRepository->findOneByEmail($email);

        if (!$user) {
            throw new AuthenticationException('Credenciais inválidas.');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $plainPassword)) {
            throw new AuthenticationException('Credenciais inválidas.');
        }

        $sessao = $this->sessaoService->criarSessao($user, $userAgent, $ip);

        $accessToken = $this->tokenService->createAccessToken($user, $sessao, $this->accessTokenTtlSeconds);

        return [
            'access_token' => $accessToken,
            'expires_in' => $this->accessTokenTtlSeconds,
            'refresh_token' => $sessao->getRefreshToken(),
            'session_id' => $sessao->getId(),
        ];
    }

    /**
     * Usa um refresh token para gerar novo access token (e rotaciona o refresh token).
     *
     * @return array same shape as login()
     *
     * @throws \RuntimeException when refresh invalid/expired/revoked
     */
    public function refreshToken(string $refreshToken, string $userAgent = '', string $ip = ''): array
    {
        $sessao = $this->sessaoService->validarRefreshToken($refreshToken);

        $this->sessaoService->atualizarUltimoUso($sessao);

        $sessao = $this->sessaoService->renovarSessao($sessao);

        $user = $sessao->getUsuario();
        $accessToken = $this->tokenService->createAccessToken($user, $sessao, $this->accessTokenTtlSeconds);

        return [
            'access_token' => $accessToken,
            'expires_in' => $this->accessTokenTtlSeconds,
            'refresh_token' => $sessao->getRefreshToken(),
            'session_id' => $sessao->getId(),
        ];
    }

    private function createUsuer(RegisterRequest $dto): Usuario
    {
        $user = new Usuario();

        $user->setNome($dto->nome);
        $user->setEmail($dto->email);

        $hash = $this->passwordHasher->hashPassword($user, $dto->senha);
        $user->setSenhaHash($hash);

        return $user;
    }

    private function createSession(Usuario $usuario): array
    {
        $sessao = $this->sessaoService->criarSessao($usuario);
        $accessToken = $this->tokenService->createAccessToken($usuario, $sessao, $this->accessTokenTtlSeconds);

        return [
            'accessToken'  => $accessToken,
            'refreshToken' => $sessao->getRefreshToken(),
            'expiraEm'     => $sessao->getDataExpiracao()->format('Y-m-d H:i:s'),
        ];
    }

    public function register(RegisterRequest $dto): array
    {
        $this->checkExistsEmail($dto->email);

        $user = $this->createUsuer($dto);

        $this->usuarioRepository->save($user);

        return $this->createSession($user);
    }

    /**
     * @throws RandomException
     */
    public function emailSending(string $email): ?array
    {
        $this->checkExistsEmail($email);

        if($this->redis->exists(self::EMAIL_TOKEN_FINISH.$email)) {
            throw new AccessDeniedHttpException('Email em utilização. Tente novamente em alguns minutos.');
        }

        $token = strtoupper(bin2hex(random_bytes(3)));

        $expirationTimeInSeconds = 60 * 3;
        $this->redis->set(self::EMAIL_TOKEN_START . $email, $token, $expirationTimeInSeconds);

        $response = [
            'message' => 'Email enviado com sucesso! Cheque sua caixa de entrada.'
        ];

        if(getenv('APP_ENV') === 'dev') {
            $response['token'] = $token;
        }

        // ENVIAR O EMAIL AQUI

        return $response;
    }

    /**
     * @throws RandomException
     */
    public function emailConfirm(string $token, string $email): ApiResponse
    {
        $savedToken = $this->redis->get(self::EMAIL_TOKEN_START.$email);

        if ($savedToken !== $token) {
            throw new
            NotFoundHttpException('Token de confirmação inválido ou expirado. Tente registrar-se novamente.');
        }

        $this->redis->delete(self::EMAIL_TOKEN_START.$email);

        // O email fica 'travado' para criação por 5 minutos.
        $expirationTimeInSeconds = 60 * 5;

        // Gero um novo token que deve ser enviado ao completar o cadastro
        $token = strtoupper(bin2hex(random_bytes(3)));

        if(getenv('APP_ENV') === 'dev') {
            $expirationTimeInSeconds = 60;
        }

        $this->redis->set(self::EMAIL_TOKEN_FINISH.$email, $token, $expirationTimeInSeconds);

        return new ApiResponse([
            'message' => 'Email confirmado com sucesso!',
            'token' => $token
        ]);
    }

    public function signupFinish(RegisterRequest $dto, string $token): array
    {
        $this->checkExistsEmail($dto->email);

        $savedToken = $this->redis->get(self::EMAIL_TOKEN_START.$dto->email);

        if ($savedToken !== $token) {
            throw new
            NotFoundHttpException('Token de confirmação inválido ou expirado. Tente registrar-se novamente.');
        }

        $user = $this->createUsuer($dto);

        $this->usuarioRepository->save($user);

        return $this->createSession($user);
    }

    private function checkExistsEmail(string $email)
    {
        $existing = $this->usuarioRepository->findOneByEmail($email);

        if ($existing) {
            throw new \DomainException("Email já cadastrado.");
        }
    }

    /**
     * Logout: revoga uma sessão identificada pelo refresh token.
     */
    public function logoutByRefreshToken(string $refreshToken): void
    {
        $sessao = $this->sessaoService->validarRefreshToken($refreshToken);

        $this->sessaoService->revogarSessao($sessao);
    }

    /**
     * Logout global do usuário (revoga todas as sessões)
     */
    public function logoutAll(Usuario $usuario): int
    {
        return $this->sessaoService->revogarTodas($usuario);
    }

    /**
     * Gera access token para um usuário e sessão existentes (útil para testes/admin).
     */
    public function generateAccessTokenForSession(UsuarioSessao $sessao): string
    {
        if ($sessao->isRevogado()) {
            throw new \RuntimeException('Sessão revogada.');
        }
        return $this->tokenService->createAccessToken($sessao->getUsuario(), $sessao, $this->accessTokenTtlSeconds);
    }
}
