<?php

declare(strict_types=1);

namespace App\Tests\Imports\Infrastructure;

use App\Imports\Infrastructure\Csv\NativeCsvReader;
use App\Shared\Domain\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class NativeCsvReaderTest extends TestCase
{
    public function testReadsCommaCsvWithLineNumbers(): void
    {
        $stream = fopen(__DIR__.'/../Fixtures/values-iso.csv', 'r');
        self::assertIsResource($stream);
        $reader = new NativeCsvReader();
        $inspection = $reader->inspect($stream);
        $rows = iterator_to_array($reader->rows($stream, $inspection));
        fclose($stream);
        self::assertSame(',', $inspection->delimiter);
        self::assertSame(2, $rows[0]->number);
        self::assertSame('MAT-001', $rows[0]->data['code']);
    }

    public function testReadsSemicolonAndPtBr(): void
    {
        $stream = fopen(__DIR__.'/../Fixtures/values-pt-br.csv', 'r');
        self::assertIsResource($stream);
        $reader = new NativeCsvReader();
        $inspection = $reader->inspect($stream);
        self::assertSame(';', $inspection->delimiter);
        self::assertCount(1, iterator_to_array($reader->rows($stream, $inspection)));
        fclose($stream);
    }

    public function testUtf8BomIsSupported(): void
    {
        $stream = fopen('php://temp', 'w+');
        self::assertIsResource($stream);
        fwrite($stream, "\xEF\xBB\xBFname,id\nExample,1\n");
        $reader = new NativeCsvReader();
        $inspection = $reader->inspect($stream);
        self::assertSame('UTF-8-BOM', $inspection->encoding);
        self::assertSame('name', $inspection->headers[0]);
        fclose($stream);
    }

    public function testBinaryIsRejected(): void
    {
        $stream = fopen('php://temp', 'w+');
        self::assertIsResource($stream);
        fwrite($stream, "a,b\0c\n1,2\n");
        $this->expectException(DomainException::class);
        (new NativeCsvReader())->inspect($stream);
    }

    public function testRowLimitIsEnforced(): void
    {
        $stream = fopen('php://temp', 'w+');
        self::assertIsResource($stream);
        fwrite($stream, "a,b\n1,2\n3,4\n");
        $reader = new NativeCsvReader(maxRows: 1);
        $inspection = $reader->inspect($stream);
        $this->expectException(DomainException::class);
        iterator_to_array($reader->rows($stream, $inspection));
    }

    public function testQuotedMultilineFieldIsReadAsText(): void
    {
        $stream = fopen('php://temp', 'w+');
        self::assertIsResource($stream);
        fwrite($stream, "id,description\n1,\"first line\nsecond line\"\n");
        $reader = new NativeCsvReader();
        $inspection = $reader->inspect($stream);
        $rows = iterator_to_array($reader->rows($stream, $inspection));
        self::assertSame("first line\nsecond line", $rows[0]->data['description']);
        fclose($stream);
    }

    public function testUnclosedQuoteIsRejected(): void
    {
        $stream = fopen('php://temp', 'w+');
        self::assertIsResource($stream);
        fwrite($stream, "id,description\n1,\"unclosed\n");
        $this->expectException(DomainException::class);
        (new NativeCsvReader())->inspect($stream);
    }

    public function testQuoteInsideUnquotedFieldIsRejected(): void
    {
        $stream = fopen('php://temp', 'w+');
        self::assertIsResource($stream);
        fwrite($stream, "id,description\n1,invalid\"quote\n");
        $this->expectException(DomainException::class);
        (new NativeCsvReader())->inspect($stream);
    }

    public function testWindows1252IsConvertedToUtf8(): void
    {
        $stream = fopen('php://temp', 'w+');
        self::assertIsResource($stream);
        $encoded = iconv('UTF-8', 'Windows-1252', "id,name\n1,Razão\n");
        self::assertIsString($encoded);
        fwrite($stream, $encoded);
        $reader = new NativeCsvReader();
        $inspection = $reader->inspect($stream);
        self::assertSame('WINDOWS-1252', $inspection->encoding);
        $rows = iterator_to_array($reader->rows($stream, $inspection));
        self::assertSame('Razão', $rows[0]->data['name']);
        fclose($stream);
    }

    public function testUnexpectedDelimiterIsRejected(): void
    {
        $stream = fopen('php://temp', 'w+');
        self::assertIsResource($stream);
        fwrite($stream, "id\tname\n1\tExample\n");
        $this->expectException(DomainException::class);
        (new NativeCsvReader())->inspect($stream);
    }
}
