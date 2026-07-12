<?php

declare(strict_types=1);

namespace App\Tests\Shared\Presentation\Http;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Presentation\Http\ApiExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class ApiExceptionSubscriberTest extends TestCase
{
    public function testDomainExceptionIsMappedToTheApiContract(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $exception = new DomainException(
            'ORGANIZATION_CONTEXT_REQUIRED',
            'Nenhuma organização ativa está disponível.',
            403,
        );
        $event = new ExceptionEvent(
            $kernel,
            Request::create('/api/v1/example'),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );

        (new ApiExceptionSubscriber(new NullLogger()))($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(403, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            <<<'JSON'
            {
                "data": null,
                "meta": {},
                "errors": [
                    {
                        "code": "ORGANIZATION_CONTEXT_REQUIRED",
                        "message": "Nenhuma organização ativa está disponível."
                    }
                ]
            }
            JSON,
            (string) $response->getContent(),
        );
    }
}
