<?php

declare(strict_types=1);

namespace App\Tests\Imports\Infrastructure;

use App\Imports\Infrastructure\Storage\LocalImportFileStorage;
use App\Shared\Domain\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class LocalImportFileStorageTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/safescore-import-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->directory)) {
            foreach (glob($this->directory.'/*') ?: [] as $file) {
                unlink($file);
            } rmdir($this->directory);
        }
    }

    public function testUsesSafeGeneratedKeyAndSanitizesOriginalName(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'csv');
        self::assertIsString($source);
        file_put_contents($source, "a,b\n1,2\n");
        $storage = new LocalImportFileStorage($this->directory);
        $stored = $storage->store($source, '../../unsafe.csv');
        self::assertMatchesRegularExpression('/^[a-f0-9]{48}\.csv$/', $stored->storageKey);
        self::assertSame('unsafe.csv', $stored->originalFileName);
        self::assertTrue($storage->exists($stored->storageKey));
        $storage->remove($stored->storageKey);
        unlink($source);
    }

    public function testRejectsWrongExtension(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'csv');
        self::assertIsString($source);
        file_put_contents($source, 'data');
        $this->expectException(DomainException::class);
        (new LocalImportFileStorage($this->directory))->store($source, 'payload.exe');
    }

    public function testRejectsEmptyFile(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'csv');
        self::assertIsString($source);
        $this->expectException(DomainException::class);
        (new LocalImportFileStorage($this->directory))->store($source, 'empty.csv');
    }

    public function testRejectsFileAboveConfiguredLimit(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'csv');
        self::assertIsString($source);
        file_put_contents($source, "a,b\n123,456\n");
        $this->expectException(DomainException::class);
        (new LocalImportFileStorage($this->directory, 4))->store($source, 'large.csv');
    }

    public function testRejectsPathTraversalAsStorageKey(): void
    {
        $this->expectException(DomainException::class);
        (new LocalImportFileStorage($this->directory))->open('../secret.csv');
    }

    public function testMissingSafeKeyReturnsDomainError(): void
    {
        $this->expectException(DomainException::class);
        (new LocalImportFileStorage($this->directory))->open(str_repeat('a', 48).'.csv');
    }
}
