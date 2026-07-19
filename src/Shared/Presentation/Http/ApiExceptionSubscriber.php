<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use App\Shared\Application\Observability\CorrelationIdProviderInterface;
use App\Shared\Domain\Exception\DomainException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;

#[AsEventListener(event: 'kernel.exception')]
final readonly class ApiExceptionSubscriber
{
    public function __construct(
        private LoggerInterface $logger,
        private ?CorrelationIdProviderInterface $correlationIds = null,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof AuthenticationException || $exception instanceof AccessDeniedException) {
            return;
        }

        if ($exception instanceof DomainException) {
            $event->setResponse(ApiResponseFactory::error([
                new ApiError(
                    $exception->errorCode(),
                    $exception->getMessage(),
                    $exception->field(),
                ),
            ], $exception->statusCode(), $this->errorMeta()));

            return;
        }

        if ($exception instanceof ExtraAttributesException) {
            $event->setResponse(ApiResponseFactory::error([
                new ApiError('BAD_REQUEST', 'O payload contém campos não permitidos.'),
            ], 400, $this->errorMeta()));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $event->setResponse(ApiResponseFactory::error([
                new ApiError($this->httpErrorCode($status), $this->safeHttpMessage($status)),
            ], $status, $this->errorMeta()));

            return;
        }

        $this->logger->error('Unhandled application exception: {exception_class}: {exception_message}', [
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
            'exception' => $exception,
        ]);

        $event->setResponse(ApiResponseFactory::error([
            new ApiError('INTERNAL_ERROR', 'Ocorreu um erro interno.'),
        ], 500, $this->errorMeta()));
    }

    /** @return array{correlation_id: string}|array{} */
    private function errorMeta(): array
    {
        return null === $this->correlationIds
            ? []
            : ['correlation_id' => $this->correlationIds->current()];
    }

    private function httpErrorCode(int $status): string
    {
        return match ($status) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHENTICATED',
            403 => 'ACCESS_DENIED',
            404 => 'RESOURCE_NOT_FOUND',
            405 => 'METHOD_NOT_ALLOWED',
            409 => 'CONFLICT',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMIT_EXCEEDED',
            default => 'HTTP_ERROR',
        };
    }

    private function safeHttpMessage(int $status): string
    {
        return match ($status) {
            400 => 'Requisição inválida.',
            401 => 'Autenticação necessária.',
            403 => 'Acesso negado.',
            404 => 'Recurso não encontrado.',
            405 => 'Método não permitido.',
            409 => 'A requisição conflita com o estado atual do recurso.',
            422 => 'Os dados informados são inválidos.',
            429 => 'Muitas requisições. Tente novamente mais tarde.',
            default => 'Não foi possível processar a requisição.',
        };
    }
}
