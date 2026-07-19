<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260719050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Identity and organization lookup indexes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_user_status ON app_user (status)');
        $this->addSql('CREATE INDEX idx_organization_status ON organization (status)');
        $this->addSql('CREATE INDEX idx_membership_user_status_organization ON organization_membership (user_id, status, organization_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_membership_user_status_organization ON organization_membership');
        $this->addSql('DROP INDEX idx_organization_status ON organization');
        $this->addSql('DROP INDEX idx_user_status ON app_user');
    }
}
