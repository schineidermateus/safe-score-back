<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tenant-scoped credit limits and the audit log.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE credit_limits (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            organization_id INT UNSIGNED NOT NULL,
            customer_id INT UNSIGNED NOT NULL,
            approved_by_user_id INT UNSIGNED DEFAULT NULL,
            amount NUMERIC(19, 2) NOT NULL,
            valid_from DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\',
            valid_until DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\',
            status VARCHAR(20) NOT NULL,
            reason VARCHAR(1000) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_credit_limit_org_customer_period (organization_id, customer_id, status, valid_from, valid_until),
            INDEX idx_credit_limit_org_customer_history (organization_id, customer_id, created_at, id),
            INDEX idx_credit_limit_organization (organization_id),
            INDEX idx_credit_limit_customer (customer_id),
            INDEX idx_credit_limit_org_status (organization_id, status),
            INDEX idx_credit_limit_approved_by (approved_by_user_id),
            PRIMARY KEY(id),
            CONSTRAINT fk_credit_limit_organization FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE RESTRICT,
            CONSTRAINT fk_credit_limit_customer FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE RESTRICT,
            CONSTRAINT fk_credit_limit_approved_by FOREIGN KEY (approved_by_user_id) REFERENCES app_user (id) ON DELETE RESTRICT,
            CONSTRAINT chk_credit_limit_amount_positive CHECK (amount > 0),
            CONSTRAINT chk_credit_limit_period CHECK (valid_until IS NULL OR valid_until >= valid_from)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE audit_log (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            organization_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(100) NOT NULL,
            entity_id INT UNSIGNED NOT NULL,
            before_data JSON DEFAULT NULL,
            after_data JSON DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_audit_organization (organization_id),
            INDEX idx_audit_org_created (organization_id, created_at),
            INDEX idx_audit_org_entity (organization_id, entity_type, entity_id),
            INDEX idx_audit_user (user_id),
            PRIMARY KEY(id),
            CONSTRAINT fk_audit_log_organization FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE RESTRICT,
            CONSTRAINT fk_audit_log_user FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE RESTRICT
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE credit_limits');
    }
}
