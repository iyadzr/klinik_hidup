<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250621000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status field to consultation table';
    }

    public function up(Schema $schema): void
    {
        // Add status column to consultation table
        $this->addSql('ALTER TABLE consultation ADD status VARCHAR(50) DEFAULT \'pending\'');
    }

    public function down(Schema $schema): void
    {
        // Remove status column from consultation table
        $this->addSql('ALTER TABLE consultation DROP status');
    }
} 