<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tenant-scoped receivables and immutable payment history.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE receivables (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL, organization_id INT UNSIGNED NOT NULL,
            customer_id INT UNSIGNED NOT NULL, cancelled_by_user_id INT UNSIGNED DEFAULT NULL,
            source VARCHAR(50) NOT NULL, external_id VARCHAR(150) DEFAULT NULL, document_number VARCHAR(100) NOT NULL,
            issue_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)', due_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
            original_amount NUMERIC(19, 2) NOT NULL, open_amount NUMERIC(19, 2) NOT NULL, paid_amount NUMERIC(19, 2) NOT NULL,
            payment_date DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)', status VARCHAR(20) NOT NULL,
            cancelled_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', cancellation_reason VARCHAR(1000) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            UNIQUE INDEX uniq_receivable_org_source_external (organization_id, source, external_id),
            INDEX idx_receivable_org_customer_due (organization_id, customer_id, due_date, id),
            INDEX idx_receivable_org_due (organization_id, due_date, id), INDEX idx_receivable_org_status_due (organization_id, status, due_date),
            INDEX idx_receivable_organization (organization_id), INDEX idx_receivable_customer (customer_id), INDEX idx_receivable_cancelled_by (cancelled_by_user_id),
            PRIMARY KEY(id),
            CONSTRAINT fk_receivable_organization FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE RESTRICT,
            CONSTRAINT fk_receivable_customer FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE RESTRICT,
            CONSTRAINT fk_receivable_cancelled_by FOREIGN KEY (cancelled_by_user_id) REFERENCES app_user (id) ON DELETE RESTRICT,
            CONSTRAINT chk_receivable_dates CHECK (due_date >= issue_date), CONSTRAINT chk_receivable_amounts CHECK (original_amount >= 0 AND open_amount >= 0 AND paid_amount >= 0 AND open_amount + paid_amount = original_amount)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE receivable_payments (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL, organization_id INT UNSIGNED NOT NULL, receivable_id INT UNSIGNED NOT NULL,
            created_by_user_id INT UNSIGNED NOT NULL, amount NUMERIC(19, 2) NOT NULL,
            payment_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            INDEX idx_receivable_payment_org_receivable_date (organization_id, receivable_id, payment_date, id),
            INDEX idx_receivable_payment_organization (organization_id), INDEX idx_receivable_payment_receivable (receivable_id), INDEX idx_receivable_payment_created_by (created_by_user_id),
            PRIMARY KEY(id),
            CONSTRAINT fk_receivable_payment_organization FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE RESTRICT,
            CONSTRAINT fk_receivable_payment_receivable FOREIGN KEY (receivable_id) REFERENCES receivables (id) ON DELETE RESTRICT,
            CONSTRAINT fk_receivable_payment_created_by FOREIGN KEY (created_by_user_id) REFERENCES app_user (id) ON DELETE RESTRICT,
            CONSTRAINT chk_receivable_payment_amount CHECK (amount > 0)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE receivable_payments');
        $this->addSql('DROP TABLE receivables');
    }
}
