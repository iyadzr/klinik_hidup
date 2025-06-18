<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add user relationship to doctor table
 */
final class Version20250123000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_id foreign key to doctor table to link doctors with user accounts';
    }

    public function up(Schema $schema): void
    {
        // Add user_id column to doctor table
        $this->addSql('ALTER TABLE doctor ADD user_id INT DEFAULT NULL');
        
        // Add foreign key constraint
        $this->addSql('ALTER TABLE doctor ADD CONSTRAINT FK_1FC0F36AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        
        // Add index for performance
        $this->addSql('CREATE INDEX IDX_1FC0F36AA76ED395 ON doctor (user_id)');
    }

    public function down(Schema $schema): void
    {
        // Remove foreign key constraint
        $this->addSql('ALTER TABLE doctor DROP FOREIGN KEY FK_1FC0F36AA76ED395');
        
        // Remove index
        $this->addSql('DROP INDEX IDX_1FC0F36AA76ED395 ON doctor');
        
        // Remove user_id column
        $this->addSql('ALTER TABLE doctor DROP user_id');
    }
} 