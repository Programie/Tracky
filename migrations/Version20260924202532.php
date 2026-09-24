<?php
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924202532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix size of JSON field for scrobble queue';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scrobblequeue CHANGE json json LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scrobblequeue CHANGE json json VARCHAR(255) NOT NULL');
    }
}
