<?php
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260703194518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Initial database setup";
    }

    public function up(Schema $schema): void
    {
        // Check for legacy database schema
        if ($schema->hasTable("views") and $schema->getTable("views")->hasIndex("user")) {
            $this->doLegacyMigration($schema);
            return;
        }

        $this->addSql('CREATE TABLE episodes (id INT AUTO_INCREMENT NOT NULL, season INT DEFAULT NULL, number INT NOT NULL, title VARCHAR(255) NOT NULL, firstAired DATE DEFAULT NULL, plot LONGTEXT DEFAULT NULL, posterImageUrl VARCHAR(255) DEFAULT NULL, runtime INT DEFAULT NULL, INDEX IDX_7DD55EDDF0E45BA9 (season), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE movies (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, tagline VARCHAR(255) DEFAULT NULL, year INT DEFAULT NULL, tmdbId INT DEFAULT NULL, tvdbId INT DEFAULT NULL, dataProvider ENUM(\'tmdb\', \'tvdb\'), language VARCHAR(255) DEFAULT NULL, plot LONGTEXT DEFAULT NULL, posterImageUrl VARCHAR(255) DEFAULT NULL, runtime INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE scrobblequeue (id INT AUTO_INCREMENT NOT NULL, user INT DEFAULT NULL, json VARCHAR(255) NOT NULL, dateTime DATETIME NOT NULL, INDEX IDX_9C8F621D8D93D649 (user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE seasons (id INT AUTO_INCREMENT NOT NULL, number INT NOT NULL, posterImageUrl VARCHAR(255) DEFAULT NULL, `show` INT DEFAULT NULL, INDEX IDX_B4F4301C9791E97E (`show`), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shows (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, status ENUM(\'upcoming\', \'continuing\', \'ended\'), lastUpdate DATETIME DEFAULT NULL, tmdbId INT DEFAULT NULL, tvdbId INT DEFAULT NULL, dataProvider ENUM(\'tmdb\', \'tvdb\'), language VARCHAR(255) DEFAULT NULL, posterImageUrl VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE views (id INT AUTO_INCREMENT NOT NULL, user INT DEFAULT NULL, datetime DATETIME NOT NULL, item INT NOT NULL, type ENUM(\'episode\', \'movie\') NOT NULL, INDEX IDX_11F09C878D93D649 (user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE episodes ADD CONSTRAINT FK_7DD55EDDF0E45BA9 FOREIGN KEY (season) REFERENCES seasons (id)');
        $this->addSql('ALTER TABLE scrobblequeue ADD CONSTRAINT FK_9C8F621D8D93D649 FOREIGN KEY (user) REFERENCES users (id)');
        $this->addSql('ALTER TABLE seasons ADD CONSTRAINT FK_B4F4301C9791E97E FOREIGN KEY (`show`) REFERENCES shows (`id`)');
        $this->addSql('ALTER TABLE views ADD CONSTRAINT FK_11F09C878D93D649 FOREIGN KEY (user) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE episodes DROP FOREIGN KEY FK_7DD55EDDF0E45BA9');
        $this->addSql('ALTER TABLE scrobblequeue DROP FOREIGN KEY FK_9C8F621D8D93D649');
        $this->addSql('ALTER TABLE seasons DROP FOREIGN KEY FK_B4F4301C9791E97E');
        $this->addSql('ALTER TABLE views DROP FOREIGN KEY FK_11F09C878D93D649');
        $this->addSql('DROP TABLE episodes');
        $this->addSql('DROP TABLE movies');
        $this->addSql('DROP TABLE scrobblequeue');
        $this->addSql('DROP TABLE seasons');
        $this->addSql('DROP TABLE shows');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE views');
    }

    private function doLegacyMigration(Schema $schema): void
    {
        $tables = ["users", "shows", "seasons", "episodes", "movies", "views", "scrobblequeue"];

        // Remove all foreign keys
        foreach ($tables as $tableName) {
            $table = $schema->getTable($tableName);

            foreach ($table->getForeignKeys() as $foreignKey) {
                $this->addSql(sprintf("ALTER TABLE `%s` DROP FOREIGN KEY `%s`", $tableName, $foreignKey->getName()));
            }
        }

        // Table views
        $this->addSql("ALTER TABLE views CHANGE user user INT DEFAULT NULL, CHANGE type type ENUM('episode', 'movie') NOT NULL");
        $this->addSql("ALTER TABLE views RENAME INDEX user TO IDX_11F09C878D93D649");
        $this->addSql("ALTER TABLE views ADD CONSTRAINT FK_11F09C878D93D649 FOREIGN KEY (user) REFERENCES users (id)");

        // Table movies
        $this->addSql("ALTER TABLE movies CHANGE title title VARCHAR(255) NOT NULL, CHANGE tagline tagline VARCHAR(255) DEFAULT NULL, CHANGE plot plot LONGTEXT DEFAULT NULL, CHANGE posterImageUrl posterImageUrl VARCHAR(255) DEFAULT NULL, CHANGE language language VARCHAR(255) DEFAULT NULL");

        // Table episodes
        $this->addSql("ALTER TABLE episodes CHANGE season season INT DEFAULT NULL, CHANGE title title VARCHAR(255) NOT NULL, CHANGE plot plot LONGTEXT DEFAULT NULL, CHANGE posterImageUrl posterImageUrl VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE episodes RENAME INDEX season TO IDX_7DD55EDDF0E45BA9");
        $this->addSql("ALTER TABLE episodes ADD CONSTRAINT FK_7DD55EDDF0E45BA9 FOREIGN KEY (season) REFERENCES seasons (id)");

        // Table shows
        $this->addSql("ALTER TABLE shows CHANGE title title VARCHAR(255) NOT NULL, CHANGE posterImageUrl posterImageUrl VARCHAR(255) DEFAULT NULL, CHANGE language language VARCHAR(255) DEFAULT NULL");

        // Table seasons
        $this->addSql("ALTER TABLE seasons CHANGE `show` `show` INT DEFAULT NULL, CHANGE posterImageUrl posterImageUrl VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE seasons RENAME INDEX `show` TO IDX_B4F4301C9791E97E");
        $this->addSql("ALTER TABLE seasons ADD CONSTRAINT FK_B4F4301C9791E97E FOREIGN KEY (`show`) REFERENCES shows (`id`)");

        // Table scrobblequeue
        $this->addSql("ALTER TABLE scrobblequeue CHANGE user user INT DEFAULT NULL, CHANGE json json VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE scrobblequeue RENAME INDEX user TO IDX_9C8F621D8D93D649");
        $this->addSql("ALTER TABLE scrobblequeue ADD CONSTRAINT FK_9C8F621D8D93D649 FOREIGN KEY (user) REFERENCES users (id)");

        // Table users
        $this->addSql("DROP INDEX username ON users");
        $this->addSql("ALTER TABLE users CHANGE username username VARCHAR(255) NOT NULL, CHANGE password password VARCHAR(255) NOT NULL");
    }
}
