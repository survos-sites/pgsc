<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add command tracking and media metadata required by the released bundles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE command_process (id VARCHAR(26) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, command VARCHAR(255) NOT NULL, cli TEXT DEFAULT NULL, mode VARCHAR(16) NOT NULL, host VARCHAR(128) DEFAULT NULL, pid INT DEFAULT NULL, status VARCHAR(16) NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, exit_code INT DEFAULT NULL, memory_bytes INT DEFAULT NULL, output TEXT DEFAULT NULL, failure_message TEXT DEFAULT NULL, slots JSON DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_command_process_status ON command_process (status)');
        $this->addSql('CREATE INDEX idx_command_process_command ON command_process (command)');
        $this->addSql('CREATE INDEX idx_command_process_created ON command_process (created_at)');
        $this->addSql('ALTER TABLE media ADD dataset VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE media ADD info JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE media ADD ai_queue JSON DEFAULT \'[]\' NOT NULL');
        $this->addSql('ALTER TABLE media ADD ai_completed JSON DEFAULT \'[]\' NOT NULL');
        $this->addSql('ALTER TABLE media ADD ai_locked BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE media ADD ai_document_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE media ADD marking VARCHAR(32) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Preserve command history and media metadata.');
    }
}
