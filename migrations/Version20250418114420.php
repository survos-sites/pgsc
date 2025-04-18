<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250418114420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE artist (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, birth_year INT DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, code VARCHAR(16) NOT NULL, instagram VARCHAR(255) DEFAULT NULL, obra_count INT DEFAULT NULL, social_media TEXT DEFAULT NULL, studio_address TEXT DEFAULT NULL, studio_visitable VARCHAR(32) DEFAULT NULL, languages JSON DEFAULT NULL, gender VARCHAR(22) DEFAULT NULL, social TEXT DEFAULT NULL, timestamp TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, pronouns VARCHAR(16) DEFAULT NULL, phone VARCHAR(24) DEFAULT NULL, contact_method VARCHAR(255) DEFAULT NULL, studio VARCHAR(28) DEFAULT NULL, headshot VARCHAR(255) DEFAULT NULL, types VARCHAR(255) DEFAULT NULL, slogan VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_159968777153098 ON artist (code)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE artist_translation (id SERIAL NOT NULL, translatable_id INT DEFAULT NULL, bio TEXT DEFAULT NULL, locale VARCHAR(5) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9D53F3282C2AC5D3 ON artist_translation (translatable_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX artist_translation_unique_translation ON artist_translation (translatable_id, locale)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE easy_media__folder (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE easy_media__media (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, mime VARCHAR(255) DEFAULT NULL, size INT DEFAULT NULL, last_modified INT DEFAULT NULL, metas JSON NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE location (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, code VARCHAR(255) NOT NULL, type VARCHAR(24) DEFAULT NULL, obra_count INT DEFAULT NULL, lat DOUBLE PRECISION DEFAULT NULL, lng DOUBLE PRECISION DEFAULT NULL, marking VARCHAR(32) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE obj (id SERIAL NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE obra (id SERIAL NOT NULL, location_id INT DEFAULT NULL, artist_id INT NOT NULL, title VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, code VARCHAR(255) NOT NULL, year INT DEFAULT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, depth INT DEFAULT NULL, materials VARCHAR(255) DEFAULT NULL, price INT DEFAULT NULL, type VARCHAR(32) DEFAULT NULL, audio TEXT DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2EEE6DBD64D218E ON obra (location_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2EEE6DBDB7970CF8 ON obra (artist_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN obra.audio IS '(DC2Type:easy_media_type)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE obra_image (id SERIAL NOT NULL, obra_id INT NOT NULL, image_name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8C8006753C2672C8 ON obra_image (obra_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN obra_image.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE sacro (id VARCHAR(255) NOT NULL, extra JSON DEFAULT NULL, flickr_url VARCHAR(255) DEFAULT NULL, flickr_info JSON DEFAULT NULL, sais_id VARCHAR(255) DEFAULT NULL, image_sizes JSON DEFAULT NULL, drive_url VARCHAR(255) DEFAULT NULL, marking VARCHAR(32) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE sacro_translation (id SERIAL NOT NULL, translatable_id VARCHAR(255) DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, label VARCHAR(255) DEFAULT NULL, locale VARCHAR(5) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_89281EBB2C2AC5D3 ON sacro_translation (translatable_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX sacro_translation_unique_translation ON sacro_translation (translatable_id, locale)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE users (id SERIAL NOT NULL, email VARCHAR(180) DEFAULT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified BOOLEAN NOT NULL, code VARCHAR(48) NOT NULL, name VARCHAR(255) DEFAULT NULL, cel VARCHAR(255) DEFAULT NULL, is_artist BOOLEAN DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON users (email)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.available_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.delivered_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
                BEGIN
                    PERFORM pg_notify('messenger_messages', NEW.queue_name::text);
                    RETURN NEW;
                END;
            $$ LANGUAGE plpgsql;
        SQL);
        $this->addSql(<<<'SQL'
            DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE artist_translation ADD CONSTRAINT FK_9D53F3282C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES artist (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE obra ADD CONSTRAINT FK_2EEE6DBD64D218E FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE obra ADD CONSTRAINT FK_2EEE6DBDB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE obra_image ADD CONSTRAINT FK_8C8006753C2672C8 FOREIGN KEY (obra_id) REFERENCES obra (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sacro_translation ADD CONSTRAINT FK_89281EBB2C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES sacro (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE artist_translation DROP CONSTRAINT FK_9D53F3282C2AC5D3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE obra DROP CONSTRAINT FK_2EEE6DBD64D218E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE obra DROP CONSTRAINT FK_2EEE6DBDB7970CF8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE obra_image DROP CONSTRAINT FK_8C8006753C2672C8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sacro_translation DROP CONSTRAINT FK_89281EBB2C2AC5D3
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE artist
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE artist_translation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE easy_media__folder
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE easy_media__media
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE location
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE obj
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE obra
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE obra_image
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE sacro
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE sacro_translation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE users
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
