<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260719020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clean baseline: persisted roles and industrial capabilities.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE capabilities (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, code VARCHAR(100) NOT NULL, description VARCHAR(180) NOT NULL, UNIQUE INDEX uniq_capability_code (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE roles (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, code VARCHAR(80) NOT NULL, name VARCHAR(160) NOT NULL, UNIQUE INDEX uniq_role_code (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role_capabilities (role_id BIGINT UNSIGNED NOT NULL, capability_id BIGINT UNSIGNED NOT NULL, INDEX IDX_ROLE_CAP_ROLE (role_id), INDEX IDX_ROLE_CAP_CAPABILITY (capability_id), PRIMARY KEY(role_id, capability_id), CONSTRAINT FK_ROLE_CAP_ROLE FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE, CONSTRAINT FK_ROLE_CAP_CAPABILITY FOREIGN KEY (capability_id) REFERENCES capabilities (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE membership_roles (membership_id BIGINT UNSIGNED NOT NULL, role_id BIGINT UNSIGNED NOT NULL, INDEX IDX_MEMBERSHIP_ROLE_MEMBERSHIP (membership_id), INDEX IDX_MEMBERSHIP_ROLE_ROLE (role_id), PRIMARY KEY(membership_id, role_id), CONSTRAINT FK_MEMBERSHIP_ROLE_MEMBERSHIP FOREIGN KEY (membership_id) REFERENCES organization_membership (id) ON DELETE CASCADE, CONSTRAINT FK_MEMBERSHIP_ROLE_ROLE FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $capabilities = [
            'IMPORT_READ',
            'IMPORT_WRITE',
            'AUDIT_READ',
            'MANAGE_MEMBERS',
            'ASSIGN_OWNER',
        ];
        foreach ($capabilities as $code) {
            $description = str_replace('_', ' ', $code);
            $this->addSql('INSERT INTO capabilities (code, description) VALUES (:code, :description)', ['code' => $code, 'description' => $description]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE membership_roles');
        $this->addSql('DROP TABLE role_capabilities');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE capabilities');
    }
}
