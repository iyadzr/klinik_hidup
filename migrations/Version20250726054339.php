<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix Payment entity consultation field to allow NULL values
 */
final class Version20250726054339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make Payment entity consultation field nullable to fix payment processing';
    }

    public function up(Schema $schema): void
    {
        // Make consultation_id nullable in payment table
        $this->addSql('ALTER TABLE payment CHANGE consultation_id consultation_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert to non-nullable (this might fail if there are NULL values)
        $this->addSql('ALTER TABLE payment CHANGE consultation_id consultation_id INT NOT NULL');
    }
}