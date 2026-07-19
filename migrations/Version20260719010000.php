<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260719010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clean baseline: identity and organizations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE app_user (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, email VARCHAR(180) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX uniq_user_email (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE external_identity (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, user_id BIGINT UNSIGNED NOT NULL, issuer VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, subject VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX uniq_external_identity_issuer_subject (issuer, subject), INDEX idx_external_identity_user_status (user_id, status), PRIMARY KEY(id), CONSTRAINT fk_external_identity_user FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE organization (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, legal_name VARCHAR(180) NOT NULL, trade_name VARCHAR(180) DEFAULT NULL, document VARCHAR(14) DEFAULT NULL, status VARCHAR(20) NOT NULL, timezone VARCHAR(64) NOT NULL, currency VARCHAR(3) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX uniq_organization_document (document), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE organization_membership (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, organization_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, role VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, joined_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX uniq_membership_organization_user (organization_id, user_id), INDEX idx_membership_organization_status (organization_id, status), INDEX idx_membership_user (user_id), PRIMARY KEY(id), CONSTRAINT fk_membership_organization FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE, CONSTRAINT fk_membership_user FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE organization_membership');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE external_identity');
        $this->addSql('DROP TABLE app_user');
    }
}
