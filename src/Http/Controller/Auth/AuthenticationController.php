<?php

namespace App\Http\Controller\Auth;


use App\Domain\Usuario\Service\AuthService;
use App\Http\DTO\Usuario\EmailSendingRequest;
use App\Http\DTO\Usuario\EmailTokenRequest;
use App\Http\DTO\Usuario\LoginRequest;
use App\Http\DTO\Usuario\RefreshTokenRequest;
use App\Http\DTO\Usuario\RegisterRequest;
use App\Infrastructure\Http\ApiResponse;
use App\Shared\Service\RequestService;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(AuthenticationController::BASE_API)]
class AuthenticationController extends AbstractController
{
    public const BASE_API = '/public/auth';

    public function __construct(
        private readonly AuthService $authService,
        private readonly RequestService $requestService
    ) {}

    #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(): array
    {
        $data = $this->requestService->getContent();

        $dto = LoginRequest::fromArray($data);

        return $this->authService->login($dto->email, $dto->senha);
    }

    #[Route('/refresh', name: 'auth_refresh', methods: ['POST'])]
    public function refresh(): array
    {
        $data = $this->requestService->getContent();
        $dto = RefreshTokenRequest::fromArray($data);

        return $this->authService->refreshToken($dto->refreshToken);
    }

    #[Route('/logout', name: 'auth_logout', methods: ['POST'])]
    public function logout(Request $request): array
    {
        $token = $request->headers->get('Authorization');

        $this->authService->logout($token);

        return ['success' => true];
    }

    /**
     * @throws RandomException
     */
    #[Route('/register/email', name: 'auth_register_email_sending', methods: ['POST'])]
    public function email(): ?array
    {
        $data = $this->requestService->getContent();
        $email = EmailSendingRequest::fromArray($data);

        return $this->authService->emailSending($email->email);
    }

    #[Route('/register/confirm', name: 'auth_register_email_confirm', methods: ['POST'])]
    public function confirm(Request $request): ?ApiResponse
    {
        $data = $this->requestService->getContent();
        $token = EmailTokenRequest::fromArray($data);

        return $this->authService->emailConfirm($token->token, $token->email);
    }

    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(): array
    {
        $data = $this->requestService->getContent();
        $dto = RegisterRequest::fromArray($data);

        return $this->authService->register($dto);
    }
}
