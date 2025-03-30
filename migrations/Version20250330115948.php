<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250330115948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE artist ADD social_media TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE artist ADD studio_address TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE artist ADD studio_visitable VARCHAR(9) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE obra ADD price INT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE obra DROP price
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE artist DROP social_media
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE artist DROP studio_address
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE artist DROP studio_visitable
        SQL);
    }
}
