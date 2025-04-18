<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250418113514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE easy_media__folder DROP CONSTRAINT fk_1c446171727aca70
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_1c446171727aca70
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE easy_media__folder DROP parent_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE easy_media__media DROP CONSTRAINT fk_83d7599c162cb942
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_83d7599c162cb942
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE easy_media__media DROP folder_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sacro ADD flickr_url VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sacro ADD flickr_info JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sacro ADD sais_id VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sacro ADD image_sizes JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sacro ADD drive_url VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sacro ADD marking VARCHAR(32) DEFAULT NULL
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
            ALTER TABLE easy_media__media ADD folder_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE easy_media__media ADD CONSTRAINT fk_83d7599c162cb942 FOREIGN KEY (folder_id) REFERENCES easy_media__folder (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_83d7599c162cb942 ON easy_media__media (folder_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE easy_media__folder ADD parent_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE easy_media__folder ADD CONSTRAINT fk_1c446171727aca70 FOREIGN KEY (parent_id) REFERENCES easy_media__folder (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_1c446171727aca70 ON easy_media__folder (parent_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sacro_translation DROP CONSTRAINT FK_89281EBB2C2AC5D3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sacro DROP flickr_url
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sacro DROP flickr_info
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sacro DROP sais_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sacro DROP image_sizes
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sacro DROP drive_url
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sacro DROP marking
        SQL);
    }
}
