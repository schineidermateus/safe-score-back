<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Presentation;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class FinancialIndicatorsRouteTest extends KernelTestCase
{
    public function testOfficialRouteUsesIntegerCustomerId(): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);
        self::assertInstanceOf(RouterInterface::class, $router);
        $route = $router->getRouteCollection()->get('customer_financial_indicators_get');
        self::assertNotNull($route);
        self::assertSame('/api/v1/customers/{id}/financial-summary', $route->getPath());
        self::assertSame(['GET'], $route->getMethods());
        self::assertSame('\\d+', $route->getRequirement('id'));
    }
}
