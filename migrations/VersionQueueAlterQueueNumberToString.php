<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionQueueAlterQueueNumberToString extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change queue_number column in queue table from INT to VARCHAR(20) to store registration number.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE queue MODIFY queue_number VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE queue MODIFY queue_number INT DEFAULT NULL');
    }
}
