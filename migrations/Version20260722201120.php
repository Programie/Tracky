<?php
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260722201120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add user collections";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE usercollectionitems (id INT AUTO_INCREMENT NOT NULL, collection INT NOT NULL, type ENUM(\'show\', \'season\', \'episode\', \'movie\', \'movieset\') NOT NULL, item INT NOT NULL, addedAt DATETIME NOT NULL, INDEX IDX_41B0376EFC4D6532 (collection), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE usercollections (id INT AUTO_INCREMENT NOT NULL, user INT NOT NULL, name VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, INDEX IDX_1709E57A8D93D649 (user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE usercollectionitems ADD CONSTRAINT FK_41B0376EFC4D6532 FOREIGN KEY (collection) REFERENCES usercollections (id)');
        $this->addSql('ALTER TABLE usercollections ADD CONSTRAINT FK_1709E57A8D93D649 FOREIGN KEY (user) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE usercollectionitems DROP FOREIGN KEY FK_41B0376EFC4D6532');
        $this->addSql('ALTER TABLE usercollections DROP FOREIGN KEY FK_1709E57A8D93D649');
        $this->addSql('DROP TABLE usercollectionitems');
        $this->addSql('DROP TABLE usercollections');
    }
}
