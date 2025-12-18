<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251214140050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address ADD province VARCHAR(255) DEFAULT NULL, CHANGE street street VARCHAR(255) NOT NULL, CHANGE barangay barangay VARCHAR(255) NOT NULL, CHANGE city_municipality city_municipality VARCHAR(255) NOT NULL, CHANGE postal_code postal_code VARCHAR(255) NOT NULL, CHANGE user_id user_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address DROP province, CHANGE street street VARCHAR(255) DEFAULT NULL, CHANGE barangay barangay VARCHAR(255) DEFAULT NULL, CHANGE city_municipality city_municipality VARCHAR(255) DEFAULT NULL, CHANGE postal_code postal_code VARCHAR(255) DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
    }
}
