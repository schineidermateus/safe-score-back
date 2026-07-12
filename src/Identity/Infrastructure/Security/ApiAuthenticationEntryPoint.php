<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Shared\Presentation\Http\ApiError;
use App\Shared\Presentation\Http\ApiResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class ApiAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return ApiResponse::error([
            new ApiError('UNAUTHENTICATED', 'Autenticação necessária.'),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
