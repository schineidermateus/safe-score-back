<?php

namespace App\Infrastructure\Listener;

use App\Infrastructure\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ApiResponseListener implements EventSubscriberInterface
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onKernelView', 10],
        ];
    }

    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if ($result instanceof JsonResponse) {
            return;
        }

        $request = $event->getRequest();
        $method = $request->getMethod();

        // Status padrão
        $status = match ($method) {
            'POST' => 201,
            'DELETE' => 204,
            default => 200,
        };

        $headers = [];
        $data = $result;

        // Caso o controller tenha retornado ApiResponse
        if ($result instanceof ApiResponse) {
            $status = $result->status;
            $headers = $result->headers;
            $data = $result->data;
        }

        // DELETE: 204 No Content SEM corpo
        if ($method === 'DELETE') {
            $event->setResponse(new JsonResponse(null, 204));
            return;
        }

        // Serialização completa (JSON final)
        $json = $this->serializer->serialize($data, 'json', [
            'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ]);

        $response = new JsonResponse($json, $status, $headers, true);

        $event->setResponse($response);
    }
}
