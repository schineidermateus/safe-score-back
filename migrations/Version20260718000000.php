<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260718000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link local users to stable external identities using issuer and subject.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD identity_issuer VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL, ADD external_subject VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_external_identity ON app_user (identity_issuer, external_subject)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_user_external_identity ON app_user');
        $this->addSql('ALTER TABLE app_user DROP identity_issuer, DROP external_subject');
    }
}
