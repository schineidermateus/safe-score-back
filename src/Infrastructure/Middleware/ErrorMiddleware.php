<?php

namespace App\Infrastructure\Middleware;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class ErrorMiddleware implements EventSubscriberInterface
{
    private ?\Throwable $exception = null;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
            LoginFailureEvent::class => 'onLoginFailure',
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $this->exception = $event->getException();
    }

    public function onException(ExceptionEvent $event): void
    {
        $this->exception = $event->getThrowable();
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || $this->exception === null) {
            return;
        }

        $exception = $this->unwrapException($this->exception);

        // Define status padrão
        $statusCode = 500;

        // Caso seja uma HTTP exception (AuthenticationException, NotFoundHttpException, etc)
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        $responseData = [
            'message'     => $exception->getMessage(),
            'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
        ];

        $event->setResponse(new JsonResponse($responseData, $statusCode));
    }

    private function unwrapException(\Throwable $exception): \Throwable
    {
        // Desembrulhar HandlerFailedException (Messenger)
        $maxDeep = 0;
        while ($exception instanceof HandlerFailedException && $exception->getPrevious()) {
            if($maxDeep > 200) break;
            $exception = $exception->getPrevious();
            $maxDeep++;
        }

        return $exception;
    }
}
