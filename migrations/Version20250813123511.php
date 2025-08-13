<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250813123511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE image (code VARCHAR(255) NOT NULL, resized CLOB DEFAULT NULL, original_url CLOB DEFAULT NULL, mime_type VARCHAR(255) DEFAULT NULL, size INTEGER DEFAULT NULL, original_width INTEGER DEFAULT NULL, original_height INTEGER DEFAULT NULL, status_code INTEGER DEFAULT NULL, blur VARCHAR(255) DEFAULT NULL, context CLOB DEFAULT NULL, exif CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (code))');
        $this->addSql('CREATE TEMPORARY TABLE __temp__artist AS SELECT id, name, birth_year, email, code, instagram, obra_count, social_media, studio_address, studio_visitable, languages, gender, social, timestamp, pronouns, phone, contact_method, studio, headshot, types, slogan, drive_url, images, preferred_pronoun, on_social, preferred_contact, studio_open, tags FROM artist');
        $this->addSql('DROP TABLE artist');
        $this->addSql('CREATE TABLE artist (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, birth_year INTEGER DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, code VARCHAR(32) NOT NULL, instagram VARCHAR(255) DEFAULT NULL, obra_count INTEGER DEFAULT NULL, social_media CLOB DEFAULT NULL, studio_address CLOB DEFAULT NULL, studio_visitable VARCHAR(32) DEFAULT NULL, languages CLOB DEFAULT NULL, gender VARCHAR(22) DEFAULT NULL, social CLOB DEFAULT NULL, timestamp DATETIME DEFAULT NULL, pronouns VARCHAR(16) DEFAULT NULL, phone VARCHAR(24) DEFAULT NULL, contact_method VARCHAR(255) DEFAULT NULL, studio VARCHAR(28) DEFAULT NULL, headshot VARCHAR(255) DEFAULT NULL, types VARCHAR(255) DEFAULT NULL, slogan VARCHAR(255) DEFAULT NULL, drive_url CLOB DEFAULT NULL, image_codes CLOB DEFAULT NULL, preferred_pronoun VARCHAR(255) DEFAULT NULL, on_social VARCHAR(255) DEFAULT NULL, preferred_contact VARCHAR(255) DEFAULT NULL, studio_open VARCHAR(255) DEFAULT NULL, tags CLOB DEFAULT NULL)');
        $this->addSql('INSERT INTO artist (id, name, birth_year, email, code, instagram, obra_count, social_media, studio_address, studio_visitable, languages, gender, social, timestamp, pronouns, phone, contact_method, studio, headshot, types, slogan, drive_url, image_codes, preferred_pronoun, on_social, preferred_contact, studio_open, tags) SELECT id, name, birth_year, email, code, instagram, obra_count, social_media, studio_address, studio_visitable, languages, gender, social, timestamp, pronouns, phone, contact_method, studio, headshot, types, slogan, drive_url, images, preferred_pronoun, on_social, preferred_contact, studio_open, tags FROM __temp__artist');
        $this->addSql('DROP TABLE __temp__artist');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_159968777153098 ON artist (code)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__obra AS SELECT id, title, description, code, year, width, height, depth, materials, price, type, drive_url, images, youtube_url, location_id, artist_id FROM obra');
        $this->addSql('DROP TABLE obra');
        $this->addSql('CREATE TABLE obra (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) DEFAULT NULL, description CLOB DEFAULT NULL, code VARCHAR(255) NOT NULL, year INTEGER DEFAULT NULL, width INTEGER DEFAULT NULL, height INTEGER DEFAULT NULL, depth INTEGER DEFAULT NULL, materials VARCHAR(255) DEFAULT NULL, price INTEGER DEFAULT NULL, type VARCHAR(32) DEFAULT NULL, drive_url CLOB DEFAULT NULL, image_codes CLOB DEFAULT NULL, youtube_url VARCHAR(255) DEFAULT NULL, location_id INTEGER DEFAULT NULL, artist_id INTEGER NOT NULL, CONSTRAINT FK_2EEE6DBD64D218E FOREIGN KEY (location_id) REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2EEE6DBDB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO obra (id, title, description, code, year, width, height, depth, materials, price, type, drive_url, image_codes, youtube_url, location_id, artist_id) SELECT id, title, description, code, year, width, height, depth, materials, price, type, drive_url, images, youtube_url, location_id, artist_id FROM __temp__obra');
        $this->addSql('DROP TABLE __temp__obra');
        $this->addSql('CREATE INDEX IDX_2EEE6DBDB7970CF8 ON obra (artist_id)');
        $this->addSql('CREATE INDEX IDX_2EEE6DBD64D218E ON obra (location_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE image');
        $this->addSql('CREATE TEMPORARY TABLE __temp__artist AS SELECT id, name, birth_year, email, code, instagram, obra_count, social_media, studio_address, studio_visitable, languages, gender, social, timestamp, pronouns, phone, contact_method, studio, headshot, types, slogan, drive_url, image_codes, preferred_pronoun, on_social, preferred_contact, studio_open, tags FROM artist');
        $this->addSql('DROP TABLE artist');
        $this->addSql('CREATE TABLE artist (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, birth_year INTEGER DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, code VARCHAR(32) NOT NULL, instagram VARCHAR(255) DEFAULT NULL, obra_count INTEGER DEFAULT NULL, social_media CLOB DEFAULT NULL, studio_address CLOB DEFAULT NULL, studio_visitable VARCHAR(32) DEFAULT NULL, languages CLOB DEFAULT NULL, gender VARCHAR(22) DEFAULT NULL, social CLOB DEFAULT NULL, timestamp DATETIME DEFAULT NULL, pronouns VARCHAR(16) DEFAULT NULL, phone VARCHAR(24) DEFAULT NULL, contact_method VARCHAR(255) DEFAULT NULL, studio VARCHAR(28) DEFAULT NULL, headshot VARCHAR(255) DEFAULT NULL, types VARCHAR(255) DEFAULT NULL, slogan VARCHAR(255) DEFAULT NULL, drive_url CLOB DEFAULT NULL, images CLOB DEFAULT NULL, preferred_pronoun VARCHAR(255) DEFAULT NULL, on_social VARCHAR(255) DEFAULT NULL, preferred_contact VARCHAR(255) DEFAULT NULL, studio_open VARCHAR(255) DEFAULT NULL, tags CLOB DEFAULT NULL)');
        $this->addSql('INSERT INTO artist (id, name, birth_year, email, code, instagram, obra_count, social_media, studio_address, studio_visitable, languages, gender, social, timestamp, pronouns, phone, contact_method, studio, headshot, types, slogan, drive_url, images, preferred_pronoun, on_social, preferred_contact, studio_open, tags) SELECT id, name, birth_year, email, code, instagram, obra_count, social_media, studio_address, studio_visitable, languages, gender, social, timestamp, pronouns, phone, contact_method, studio, headshot, types, slogan, drive_url, image_codes, preferred_pronoun, on_social, preferred_contact, studio_open, tags FROM __temp__artist');
        $this->addSql('DROP TABLE __temp__artist');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_159968777153098 ON artist (code)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__obra AS SELECT id, title, description, code, year, width, height, depth, materials, price, type, drive_url, image_codes, youtube_url, location_id, artist_id FROM obra');
        $this->addSql('DROP TABLE obra');
        $this->addSql('CREATE TABLE obra (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) DEFAULT NULL, description CLOB DEFAULT NULL, code VARCHAR(255) NOT NULL, year INTEGER DEFAULT NULL, width INTEGER DEFAULT NULL, height INTEGER DEFAULT NULL, depth INTEGER DEFAULT NULL, materials VARCHAR(255) DEFAULT NULL, price INTEGER DEFAULT NULL, type VARCHAR(32) DEFAULT NULL, drive_url CLOB DEFAULT NULL, images CLOB DEFAULT NULL, youtube_url VARCHAR(255) DEFAULT NULL, location_id INTEGER DEFAULT NULL, artist_id INTEGER NOT NULL, CONSTRAINT FK_2EEE6DBD64D218E FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2EEE6DBDB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO obra (id, title, description, code, year, width, height, depth, materials, price, type, drive_url, images, youtube_url, location_id, artist_id) SELECT id, title, description, code, year, width, height, depth, materials, price, type, drive_url, image_codes, youtube_url, location_id, artist_id FROM __temp__obra');
        $this->addSql('DROP TABLE __temp__obra');
        $this->addSql('CREATE INDEX IDX_2EEE6DBD64D218E ON obra (location_id)');
        $this->addSql('CREATE INDEX IDX_2EEE6DBDB7970CF8 ON obra (artist_id)');
    }
}
