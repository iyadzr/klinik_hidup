<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250531171151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Just update NULL values in registration_number field
        // Skip altering the column to avoid conflicts
        $this->addSql(<<<'SQL'
            UPDATE queue SET registration_number = 0 WHERE registration_number IS NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE queue CHANGE registration_number registration_number VARCHAR(20) DEFAULT NULL
        SQL);
    }
}
