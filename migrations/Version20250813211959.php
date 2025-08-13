<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250813211959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE image (code VARCHAR(255) NOT NULL, resized JSON DEFAULT NULL, original_url TEXT DEFAULT NULL, mime_type VARCHAR(255) DEFAULT NULL, size INT DEFAULT NULL, original_width INT DEFAULT NULL, original_height INT DEFAULT NULL, status_code INT DEFAULT NULL, blur VARCHAR(255) DEFAULT NULL, context JSON DEFAULT NULL, exif JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (code))');
        $this->addSql('ALTER TABLE artist RENAME COLUMN images TO image_codes');
        $this->addSql('ALTER TABLE obra ADD size VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE obra RENAME COLUMN images TO image_codes');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE image');
        $this->addSql('ALTER TABLE artist RENAME COLUMN image_codes TO images');
        $this->addSql('ALTER TABLE obra DROP size');
        $this->addSql('ALTER TABLE obra RENAME COLUMN image_codes TO images');
    }
}
