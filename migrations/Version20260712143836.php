<?php
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260712143836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add moviesets table";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE moviesets (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, tmdbId INT DEFAULT NULL, tvdbId INT DEFAULT NULL, dataProvider ENUM(\'tmdb\', \'tvdb\'), language VARCHAR(255) DEFAULT NULL, plot LONGTEXT DEFAULT NULL, posterImageUrl VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE movies ADD movieset INT DEFAULT NULL');
        $this->addSql('ALTER TABLE movies ADD CONSTRAINT FK_C61EED30FC65610 FOREIGN KEY (movieset) REFERENCES moviesets (id)');
        $this->addSql('CREATE INDEX IDX_C61EED30FC65610 ON movies (movieset)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE movies DROP FOREIGN KEY FK_C61EED30FC65610');
        $this->addSql('DROP TABLE moviesets');
        $this->addSql('DROP INDEX IDX_C61EED30FC65610 ON movies');
        $this->addSql('ALTER TABLE movies DROP movieset');
    }
}
