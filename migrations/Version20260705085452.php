<?php
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260705085452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add plot column to shows and seasons";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE seasons ADD plot LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE shows ADD plot LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shows DROP plot');
        $this->addSql('ALTER TABLE seasons DROP plot');
    }
}
