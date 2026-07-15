<?php
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715190056 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add lastupdate column to moviesets";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE moviesets ADD lastUpdate DATETIME DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE moviesets DROP lastUpdate");
    }
}
