<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250310122826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artist (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, name VARCHAR(255) NOT NULL, birth_year INT DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, code VARCHAR(16) NOT NULL, instagram VARCHAR(255) DEFAULT NULL, bio TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_159968777153098 ON artist (code)');
        $this->addSql('CREATE TABLE location (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, code VARCHAR(255) NOT NULL, type VARCHAR(24) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE obra (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, title VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, code VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, location_id INT DEFAULT NULL, artist_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2EEE6DBD64D218E ON obra (location_id)');
        $this->addSql('CREATE INDEX IDX_2EEE6DBDB7970CF8 ON obra (artist_id)');
        $this->addSql('CREATE TABLE users (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON users (email)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT GENERATED BY DEFAULT AS IDENTITY NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('ALTER TABLE obra ADD CONSTRAINT FK_2EEE6DBD64D218E FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE obra ADD CONSTRAINT FK_2EEE6DBDB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE obra DROP CONSTRAINT FK_2EEE6DBD64D218E');
        $this->addSql('ALTER TABLE obra DROP CONSTRAINT FK_2EEE6DBDB7970CF8');
        $this->addSql('DROP TABLE artist');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE obra');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
