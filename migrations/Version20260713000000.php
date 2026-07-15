<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the initial SafeScore identity, organization, membership and customer schema.';
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

        $this->addSql('CREATE TABLE customer (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            organization_id INT UNSIGNED NOT NULL,
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
            PRIMARY KEY(id),
            CONSTRAINT fk_customer_organization FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE RESTRICT
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE organization_membership');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE app_user');
    }
}
