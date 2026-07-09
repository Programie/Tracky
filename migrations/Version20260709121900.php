<?php
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709121900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the settings table for storing user preferences.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE settings (id INTEGER NOT NULL AUTO_INCREMENT, user_id INTEGER NOT NULL, setting VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_SETTINGS_USER ON settings (user_id)');
        $this->addSql('ALTER TABLE settings ADD CONSTRAINT FK_SETTINGS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE settings');
    }
}
