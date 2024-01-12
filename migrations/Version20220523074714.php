<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220523074714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, cif VARCHAR(255) NOT NULL, organization VARCHAR(1024) DEFAULT NULL, dni VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, file VARCHAR(255) NOT NULL, sha1 VARCHAR(255) NOT NULL, size BIGINT NOT NULL, sender_email VARCHAR(255) NOT NULL, receiver_email VARCHAR(255) NOT NULL, issuer VARCHAR(1024) NOT NULL, registration_number VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE audit');
    }
}
