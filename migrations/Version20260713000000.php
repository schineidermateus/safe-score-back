<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create identity and organization tables and safely migrate Customer to integer IDs and organization FK.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_user (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(180) NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX uniq_user_email (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE organization (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            legal_name VARCHAR(180) NOT NULL,
            trade_name VARCHAR(180) DEFAULT NULL,
            document VARCHAR(14) DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            timezone VARCHAR(64) NOT NULL,
            currency VARCHAR(3) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX uniq_organization_document (document),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE organization_membership (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            organization_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            role VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL,
            joined_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX uniq_membership_organization_user (organization_id, user_id),
            INDEX idx_membership_organization_status (organization_id, status),
            INDEX idx_membership_user (user_id),
            PRIMARY KEY(id),
            CONSTRAINT fk_membership_organization FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE,
            CONSTRAINT fk_membership_user FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE organization_legacy_id_map (
            legacy_id VARCHAR(64) NOT NULL,
            organization_id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            UNIQUE INDEX uniq_organization_legacy_new_id (organization_id),
            PRIMARY KEY(legacy_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO organization_legacy_id_map (legacy_id)
            SELECT DISTINCT organization_id FROM customer ORDER BY organization_id');
        $this->addSql("INSERT INTO organization
            (id, legal_name, trade_name, document, status, timezone, currency, created_at, updated_at)
            SELECT organization_id,
                   CONCAT('Organização migrada ', legacy_id),
                   CONCAT('Organização ', legacy_id),
                   NULL,
                   'ACTIVE',
                   'America/Sao_Paulo',
                   'BRL',
                   CURRENT_TIMESTAMP,
                   CURRENT_TIMESTAMP
            FROM organization_legacy_id_map");

        $this->addSql('CREATE TABLE customer_legacy_id_map (
            legacy_id VARCHAR(26) NOT NULL,
            customer_id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            UNIQUE INDEX uniq_customer_legacy_new_id (customer_id),
            PRIMARY KEY(legacy_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO customer_legacy_id_map (legacy_id)
            SELECT id FROM customer ORDER BY id');

        $this->addSql('ALTER TABLE customer
            ADD new_id INT UNSIGNED DEFAULT NULL,
            ADD new_organization_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('UPDATE customer customer_row
            INNER JOIN customer_legacy_id_map customer_map ON customer_map.legacy_id = customer_row.id
            INNER JOIN organization_legacy_id_map organization_map ON organization_map.legacy_id = customer_row.organization_id
            SET customer_row.new_id = customer_map.customer_id,
                customer_row.new_organization_id = organization_map.organization_id');

        $this->addSql('ALTER TABLE customer
            DROP PRIMARY KEY,
            DROP INDEX uniq_customer_organization_document,
            DROP INDEX idx_customer_organization_status,
            DROP INDEX idx_customer_organization_external,
            DROP INDEX idx_customer_organization_deleted,
            CHANGE id legacy_id VARCHAR(26) NOT NULL,
            CHANGE new_id id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE customer
            DROP organization_id,
            CHANGE new_organization_id organization_id INT UNSIGNED NOT NULL,
            DROP legacy_id,
            ADD UNIQUE INDEX uniq_customer_organization_document (organization_id, document),
            ADD INDEX idx_customer_organization_status (organization_id, status),
            ADD INDEX idx_customer_organization_external (organization_id, external_id),
            ADD INDEX idx_customer_organization_deleted (organization_id, deleted_at),
            ADD CONSTRAINT fk_customer_organization FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE RESTRICT');

        $this->addSql('ALTER TABLE customer_legacy_id_map
            ADD CONSTRAINT fk_customer_legacy_customer FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE organization_legacy_id_map
            ADD CONSTRAINT fk_organization_legacy_organization FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'This migration preserves legacy mappings and cannot be reversed safely after new integer IDs are issued.');
    }
}
