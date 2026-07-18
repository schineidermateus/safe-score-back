<?php

declare(strict_types=1);

namespace App\Tests\Imports\Presentation;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class ImportRoutesTest extends KernelTestCase
{
    public function testAllImportRoutesExistAndIdsAreNumeric(): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);
        self::assertInstanceOf(RouterInterface::class, $router);
        $routes = $router->getRouteCollection();
        foreach (['imports_list', 'imports_create', 'imports_get', 'imports_mapping', 'imports_validate', 'imports_preview', 'imports_process', 'imports_errors', 'imports_cancel'] as $name) {
            self::assertNotNull($routes->get($name), $name);
        }
        self::assertSame('\\d+', $routes->get('imports_get')?->getRequirement('id'));
    }
}
