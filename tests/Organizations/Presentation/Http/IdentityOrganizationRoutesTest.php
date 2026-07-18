<?php

declare(strict_types=1);

namespace App\Tests\Organizations\Presentation\Http;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class IdentityOrganizationRoutesTest extends KernelTestCase
{
    public function testSpecRoutesAreRegisteredWithExpectedMethods(): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);
        self::assertInstanceOf(RouterInterface::class, $router);
        $routes = $router->getRouteCollection();

        foreach ([
            'identity_me' => ['/api/v1/me', ['GET']],
            'organizations_current' => ['/api/v1/organizations/current', ['GET']],
            'organizations_members_list' => ['/api/v1/organizations/current/members', ['GET']],
            'organizations_members_add' => ['/api/v1/organizations/current/members', ['POST']],
            'organizations_members_update' => ['/api/v1/organizations/current/members/{id}', ['PATCH']],
        ] as $name => [$path, $methods]) {
            $route = $routes->get($name);
            self::assertNotNull($route, $name);
            self::assertSame($path, $route->getPath(), $name);
            self::assertSame($methods, $route->getMethods(), $name);
        }
    }
}
