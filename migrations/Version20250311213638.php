<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250311213638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE obra_image (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, image_name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, obra_id INTEGER NOT NULL, CONSTRAINT FK_8C8006753C2672C8 FOREIGN KEY (obra_id) REFERENCES obra (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8C8006753C2672C8 ON obra_image (obra_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__obra AS SELECT id, title, description, code, year, width, height, depth, materials, location_id, artist_id FROM obra');
        $this->addSql('DROP TABLE obra');
        $this->addSql('CREATE TABLE obra (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) DEFAULT NULL, description CLOB DEFAULT NULL, code VARCHAR(255) NOT NULL, year INTEGER DEFAULT NULL, width INTEGER DEFAULT NULL, height INTEGER DEFAULT NULL, depth INTEGER DEFAULT NULL, materials VARCHAR(255) DEFAULT NULL, location_id INTEGER DEFAULT NULL, artist_id INTEGER NOT NULL, CONSTRAINT FK_2EEE6DBD64D218E FOREIGN KEY (location_id) REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2EEE6DBDB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO obra (id, title, description, code, year, width, height, depth, materials, location_id, artist_id) SELECT id, title, description, code, year, width, height, depth, materials, location_id, artist_id FROM __temp__obra');
        $this->addSql('DROP TABLE __temp__obra');
        $this->addSql('CREATE INDEX IDX_2EEE6DBDB7970CF8 ON obra (artist_id)');
        $this->addSql('CREATE INDEX IDX_2EEE6DBD64D218E ON obra (location_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE obra_image');
        $this->addSql('ALTER TABLE obra ADD COLUMN image VARCHAR(255) DEFAULT NULL');
    }
}
