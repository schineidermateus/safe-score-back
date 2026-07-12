<?php

namespace App\Infrastructure\Security;

use App\Domain\Usuario\Repository\UsuarioRepository;
use App\Domain\Usuario\Service\UsuarioSessaoService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UsuarioSessaoService $usuarioSessaoService,
        private readonly UsuarioRepository $usuarioRepository
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $token = str_replace('Bearer ', '', $request->headers->get('Authorization'));

        $session = $this->usuarioSessaoService->validarToken($token);

        return new SelfValidatingPassport(
            new UserBadge($session->getUsuario()->getId(), function ($id) {
                return $this->usuarioRepository->find($id);
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?JsonResponse
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, \Throwable $exception): ?JsonResponse
    {
        return new JsonResponse([
            'error' => 'Unauthorized',
            'message' => $exception->getMessage()
        ], 401);
    }
}
