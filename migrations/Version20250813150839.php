<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250813150839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE obra ADD COLUMN size VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__obra AS SELECT id, title, description, code, year, width, height, depth, materials, price, type, drive_url, image_codes, youtube_url, location_id, artist_id FROM obra');
        $this->addSql('DROP TABLE obra');
        $this->addSql('CREATE TABLE obra (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) DEFAULT NULL, description CLOB DEFAULT NULL, code VARCHAR(255) NOT NULL, year INTEGER DEFAULT NULL, width INTEGER DEFAULT NULL, height INTEGER DEFAULT NULL, depth INTEGER DEFAULT NULL, materials VARCHAR(255) DEFAULT NULL, price INTEGER DEFAULT NULL, type VARCHAR(32) DEFAULT NULL, drive_url CLOB DEFAULT NULL, image_codes CLOB DEFAULT NULL, youtube_url VARCHAR(255) DEFAULT NULL, location_id INTEGER DEFAULT NULL, artist_id INTEGER NOT NULL, CONSTRAINT FK_2EEE6DBD64D218E FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2EEE6DBDB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO obra (id, title, description, code, year, width, height, depth, materials, price, type, drive_url, image_codes, youtube_url, location_id, artist_id) SELECT id, title, description, code, year, width, height, depth, materials, price, type, drive_url, image_codes, youtube_url, location_id, artist_id FROM __temp__obra');
        $this->addSql('DROP TABLE __temp__obra');
        $this->addSql('CREATE INDEX IDX_2EEE6DBD64D218E ON obra (location_id)');
        $this->addSql('CREATE INDEX IDX_2EEE6DBDB7970CF8 ON obra (artist_id)');
    }
}
