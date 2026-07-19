<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability;

use App\Shared\Application\Observability\CorrelationIdProviderInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class HttpCorrelationIdProvider implements CorrelationIdProviderInterface
{
    private const ATTRIBUTE = '_correlation_id';
    private const HEADER = 'X-Correlation-ID';

    public function __construct(private RequestStack $requests)
    {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 1000)]
    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $request->attributes->set(self::ATTRIBUTE, $this->fromRequest($request));
    }

    #[AsEventListener(event: KernelEvents::RESPONSE, priority: -1000)]
    public function onResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest()) {
            $event->getResponse()->headers->set(self::HEADER, $this->current());
        }
    }

    public function current(): string
    {
        $request = $this->requests->getCurrentRequest();
        if (null === $request) {
            return bin2hex(random_bytes(16));
        }

        $correlationId = $request->attributes->get(self::ATTRIBUTE);
        if (!is_string($correlationId) || '' === $correlationId) {
            $correlationId = $this->fromRequest($request);
            $request->attributes->set(self::ATTRIBUTE, $correlationId);
        }

        return $correlationId;
    }

    private function fromRequest(Request $request): string
    {
        $provided = trim((string) $request->headers->get(self::HEADER, ''));

        return 1 === preg_match('/^[A-Za-z0-9._-]{1,100}$/', $provided)
            ? $provided
            : bin2hex(random_bytes(16));
    }
}
