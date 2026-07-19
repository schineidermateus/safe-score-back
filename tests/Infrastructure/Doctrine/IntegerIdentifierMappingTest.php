<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class IntegerIdentifierMappingTest extends KernelTestCase
{
    public function testEveryEntityUsesGeneratedIntegerIdentifier(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $all = $em->getMetadataFactory()->getAllMetadata();
        self::assertNotEmpty($all);
        foreach ($all as $mapping) {
            self::assertSame(['id'], $mapping->getIdentifierFieldNames(), $mapping->name);
            self::assertSame('integer', $mapping->getTypeOfField('id'), $mapping->name);
            self::assertSame(ClassMetadata::GENERATOR_TYPE_IDENTITY, $mapping->generatorType, $mapping->name);
            self::assertTrue($mapping->getFieldMapping('id')->options['unsigned'] ?? false, $mapping->name);
        }
    }

    public function testProjectDoesNotUseOpaqueIdentifierTypes(): void
    {
        $root = dirname(__DIR__, 3);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/src'));
        foreach ($files as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }$contents = file_get_contents($file->getPathname());
            self::assertIsString($contents);
            self::assertDoesNotMatchRegularExpression('/UUID|ULID|Ramsey\\\\Uuid|Symfony\\\\Component\\\\Uid/i', $contents, $file->getPathname());
        }
    }
}
