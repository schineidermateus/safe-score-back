<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tenant-scoped synchronous CSV import batches and rows.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('import_batches')) {
            $this->addSql('CREATE TABLE import_batches (id INT UNSIGNED AUTO_INCREMENT NOT NULL, organization_id INT UNSIGNED NOT NULL, created_by_user_id INT UNSIGNED NOT NULL, type VARCHAR(30) NOT NULL, file_name VARCHAR(255) NOT NULL, original_file_name VARCHAR(255) NOT NULL, storage_key VARCHAR(255) NOT NULL, file_hash VARCHAR(64) NOT NULL, file_size INT UNSIGNED NOT NULL, mapping JSON DEFAULT NULL, headers JSON NOT NULL, detected_encoding VARCHAR(30) NOT NULL, detected_delimiter VARCHAR(1) NOT NULL, status VARCHAR(30) NOT NULL, total_rows INT UNSIGNED DEFAULT 0 NOT NULL, valid_rows INT UNSIGNED DEFAULT 0 NOT NULL, success_rows INT UNSIGNED DEFAULT 0 NOT NULL, error_rows INT UNSIGNED DEFAULT 0 NOT NULL, skipped_rows INT UNSIGNED DEFAULT 0 NOT NULL, failure_code VARCHAR(100) DEFAULT NULL, started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_IMPORT_BATCH_STORAGE_KEY (storage_key), INDEX idx_import_batch_org_status (organization_id, status), INDEX idx_import_batch_org_type_created (organization_id, type, created_at), INDEX idx_import_batch_org_hash_type (organization_id, file_hash, type), INDEX IDX_IMPORT_BATCH_USER (created_by_user_id), PRIMARY KEY(id), CONSTRAINT FK_IMPORT_BATCH_ORG FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE RESTRICT, CONSTRAINT FK_IMPORT_BATCH_USER FOREIGN KEY (created_by_user_id) REFERENCES app_user (id) ON DELETE RESTRICT) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        }

        if (!$schema->hasTable('import_rows')) {
            $this->addSql('CREATE TABLE import_rows (id INT UNSIGNED AUTO_INCREMENT NOT NULL, import_batch_id INT UNSIGNED NOT NULL, line_number INT UNSIGNED NOT NULL, raw_data JSON NOT NULL, normalized_data JSON DEFAULT NULL, status VARCHAR(20) NOT NULL, errors JSON DEFAULT NULL, action VARCHAR(20) DEFAULT NULL, entity_type VARCHAR(50) DEFAULT NULL, entity_id INT UNSIGNED DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_import_row_batch_number (import_batch_id, line_number), INDEX idx_import_row_batch_status (import_batch_id, status), PRIMARY KEY(id), CONSTRAINT FK_IMPORT_ROW_BATCH FOREIGN KEY (import_batch_id) REFERENCES import_batches (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        }

        $customer = $schema->getTable('customer');
        if (!$customer->hasIndex('uniq_customer_organization_external')) {
            $this->addSql('CREATE UNIQUE INDEX uniq_customer_organization_external ON customer (organization_id, external_id)');
        }
        if ($customer->hasIndex('idx_customer_organization_external')) {
            $this->addSql('DROP INDEX idx_customer_organization_external ON customer');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('import_rows')) {
            $this->addSql('DROP TABLE import_rows');
        }
        if ($schema->hasTable('import_batches')) {
            $this->addSql('DROP TABLE import_batches');
        }

        $customer = $schema->getTable('customer');
        if (!$customer->hasIndex('idx_customer_organization_external')) {
            $this->addSql('CREATE INDEX idx_customer_organization_external ON customer (organization_id, external_id)');
        }
        if ($customer->hasIndex('uniq_customer_organization_external')) {
            $this->addSql('DROP INDEX uniq_customer_organization_external ON customer');
        }
    }
}
