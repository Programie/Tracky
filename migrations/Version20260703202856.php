<?php
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260703202856 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Do not allow NULL values in view type";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE views CHANGE type type ENUM(\'episode\', \'movie\') NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE views CHANGE type type ENUM(\'episode\', \'movie\') DEFAULT NULL');
    }
}
