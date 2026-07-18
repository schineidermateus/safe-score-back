<?php

declare(strict_types=1);

namespace App\Tests\Imports\Application;

use App\Imports\Application\Schema\ImportSchemaRegistry;
use App\Imports\Domain\Enum\ImportType;
use App\Shared\Domain\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class ImportSchemaRegistryTest extends TestCase
{
    public function testValidCustomerMapping(): void
    {
        (new ImportSchemaRegistry())->validateMapping(ImportType::Customers, ['Nome', 'Documento'], ['Nome' => 'legal_name', 'Documento' => 'document']);
        self::addToAssertionCount(1);
    }

    public function testMissingRequiredField(): void
    {
        $this->expectException(DomainException::class);
        (new ImportSchemaRegistry())->validateMapping(ImportType::Customers, ['Documento'], ['Documento' => 'document']);
    }

    public function testDuplicateTarget(): void
    {
        $this->expectException(DomainException::class);
        (new ImportSchemaRegistry())->validateMapping(ImportType::Customers, ['Nome', 'Apelido', 'Documento'], ['Nome' => 'legal_name', 'Apelido' => 'legal_name', 'Documento' => 'document']);
    }

    public function testFieldFromWrongType(): void
    {
        $this->expectException(DomainException::class);
        (new ImportSchemaRegistry())->validateMapping(ImportType::Customers, ['Nome', 'Valor', 'Documento'], ['Nome' => 'legal_name', 'Valor' => 'amount', 'Documento' => 'document']);
    }
}
