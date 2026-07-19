<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine;

use App\Authorization\Domain\AuthorizationAction;
use PHPUnit\Framework\TestCase;

final class PlatformFoundationBaselineTest extends TestCase
{
    public function testBaselineContainsOnlyFoundationMigrationsAndTables(): void
    {
        $root = dirname(__DIR__, 3);
        $migrationFiles = glob($root.'/migrations/Version*.php');
        self::assertIsArray($migrationFiles);
        sort($migrationFiles);
        self::assertCount(4, $migrationFiles);

        $sql = '';
        foreach ($migrationFiles as $file) {
            $contents = file_get_contents($file);
            self::assertIsString($contents);
            $sql .= $contents;
        }

        foreach ([
            'app_user',
            'external_identity',
            'organization',
            'organization_membership',
            'capabilities',
            'roles',
            'role_capabilities',
            'membership_roles',
            'import_batches',
            'import_rows',
            'audit_log',
        ] as $table) {
            self::assertStringContainsString('CREATE TABLE '.$table, $sql);
        }

        foreach ([
            'customers',
            'credit_limits',
            'receivables',
            'business_partners',
            'materials',
            'quarries',
            'storage_locations',
            'machines',
        ] as $table) {
            self::assertStringNotContainsString('CREATE TABLE '.$table, $sql);
        }

        self::assertStringNotContainsString('id INT UNSIGNED', $sql);
        self::assertStringContainsString('id BIGINT UNSIGNED AUTO_INCREMENT', $sql);
        self::assertStringNotContainsString('password_hash', $sql);
        self::assertStringContainsString('uniq_external_identity_issuer_subject', $sql);
    }

    public function testCapabilityCatalogIsLimitedToImplementedFoundationActions(): void
    {
        self::assertSame([
            'IMPORT_READ',
            'IMPORT_WRITE',
            'AUDIT_READ',
            'MANAGE_MEMBERS',
            'ASSIGN_OWNER',
        ], array_map(
            static fn (AuthorizationAction $action): string => $action->value,
            AuthorizationAction::cases(),
        ));
    }

    public function testFixturesDoNotDependOnIndustrialModules(): void
    {
        $root = dirname(__DIR__, 3);
        $fixtures = file_get_contents($root.'/src/Shared/Infrastructure/Fixtures/AppFixtures.php');
        self::assertIsString($fixtures);
        self::assertStringNotContainsString('App\\Industrial', $fixtures);
        self::assertDirectoryDoesNotExist($root.'/src/Industrial');
    }
}
