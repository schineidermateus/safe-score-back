<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260719040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clean baseline: industrial foundation.';
    }

    public function up(Schema $schema): void
    {
        $this->foundation('business_partners', 'legal_name VARCHAR(180) NOT NULL, trade_name VARCHAR(180) DEFAULT NULL, type VARCHAR(40) NOT NULL', 'uniq_business_partner_org_code', true);
        $this->foundation('materials', '', 'uniq_material_org_code');
        $this->foundation('quarries', '', 'uniq_quarry_org_code');
        $this->foundation('storage_locations', '', 'uniq_storage_location_org_code');
        $this->foundation('machines', '', 'uniq_machine_org_code');
    }

    private function foundation(string $table, string $extra, string $unique, bool $partner = false): void
    {
        $name = $partner ? '' : 'name VARCHAR(180) NOT NULL,';
        $extra = '' === $extra ? '' : $extra.',';
        $statusIndex = $partner ? ', INDEX idx_business_partner_org_status (organization_id, status)' : '';
        $this->addSql("CREATE TABLE {$table} (id INT UNSIGNED AUTO_INCREMENT NOT NULL, organization_id INT UNSIGNED NOT NULL, code VARCHAR(100) NOT NULL, {$name} {$extra} status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX {$unique} (organization_id, code){$statusIndex}, INDEX IDX_{$table}_ORG (organization_id), PRIMARY KEY(id), CONSTRAINT FK_{$table}_ORG FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE RESTRICT) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        foreach (['machines', 'storage_locations', 'quarries', 'materials', 'business_partners'] as $table) {
            $this->addSql('DROP TABLE '.$table);
        }
    }
}
