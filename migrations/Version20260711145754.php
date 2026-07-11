<?php
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260711145754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Rename settings table to usersettings (recreate)";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE usersettings (id INT AUTO_INCREMENT NOT NULL, user INT NOT NULL, name VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, INDEX IDX_C9005FB28D93D649 (user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE usersettings ADD CONSTRAINT FK_C9005FB28D93D649 FOREIGN KEY (user) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE settings DROP FOREIGN KEY FK_SETTINGS_USER');
        $this->addSql('DROP TABLE settings');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE settings (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, setting VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, value VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`, INDEX IDX_SETTINGS_USER (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE settings ADD CONSTRAINT FK_SETTINGS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE usersettings DROP FOREIGN KEY FK_C9005FB28D93D649');
        $this->addSql('DROP TABLE usersettings');
    }
}
