<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251217224755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD oauth_provider VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD oauth_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD avatar VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD oauth_access_token TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD oauth_refresh_token TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD oauth_token_expires TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN users.oauth_token_expires IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9444F97DD ON users (phone)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX UNIQ_1483A5E9444F97DD');
        $this->addSql('ALTER TABLE users DROP oauth_provider');
        $this->addSql('ALTER TABLE users DROP oauth_id');
        $this->addSql('ALTER TABLE users DROP avatar');
        $this->addSql('ALTER TABLE users DROP oauth_access_token');
        $this->addSql('ALTER TABLE users DROP oauth_refresh_token');
        $this->addSql('ALTER TABLE users DROP oauth_token_expires');
    }
}
