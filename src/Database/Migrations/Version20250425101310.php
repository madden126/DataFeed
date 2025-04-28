<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250425101310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the products table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE products (
            id INT AUTO_INCREMENT NOT NULL,
            gtin CHAR(14) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
            language VARCHAR(5) DEFAULT NULL,
            title VARCHAR(255) DEFAULT NULL,
            picture VARCHAR(255) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            price DECIMAL(10,2) DEFAULT NULL,
            stock INT DEFAULT 0,
            date_add DATETIME DEFAULT CURRENT_TIMESTAMP,
            date_upd DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_gtin (gtin)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE IF EXISTS products');
    }
}
