<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250130000000_FixConsultationStatusLength extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix consultation and queue status column length to support completed_consultation status';
    }

    public function up(Schema $schema): void
    {
        // Fix status columns to support 'completed_consultation' (22 characters)
        $this->addSql('ALTER TABLE consultation MODIFY COLUMN status VARCHAR(50) DEFAULT \'pending\'');
        $this->addSql('ALTER TABLE queue MODIFY COLUMN status VARCHAR(50) DEFAULT \'waiting\'');
    }

    public function down(Schema $schema): void
    {
        // Revert to original lengths
        $this->addSql('ALTER TABLE consultation MODIFY COLUMN status VARCHAR(20) DEFAULT \'pending\'');
        $this->addSql('ALTER TABLE queue MODIFY COLUMN status VARCHAR(20) DEFAULT \'waiting\'');
    }
} 