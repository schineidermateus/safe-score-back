<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260712000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the tenant-aware customer table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE customer (
                id VARCHAR(26) NOT NULL,
                organization_id VARCHAR(64) NOT NULL,
                external_id VARCHAR(100) DEFAULT NULL,
                legal_name VARCHAR(180) NOT NULL,
                trade_name VARCHAR(180) DEFAULT NULL,
                document VARCHAR(14) DEFAULT NULL,
                segment VARCHAR(100) DEFAULT NULL,
                status VARCHAR(20) NOT NULL,
                account_manager VARCHAR(120) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX uniq_customer_organization_document (organization_id, document),
                INDEX idx_customer_organization_status (organization_id, status),
                INDEX idx_customer_organization_external (organization_id, external_id),
                INDEX idx_customer_organization_deleted (organization_id, deleted_at),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE customer');
    }
}
