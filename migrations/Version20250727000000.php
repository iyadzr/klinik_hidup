<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add consultation_id foreign key to Queue entity
 */
final class Version20250727000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add consultation_id foreign key to Queue table to establish proper relationship';
    }

    public function up(Schema $schema): void
    {
        // Add consultation_id column to queue table
        $this->addSql('ALTER TABLE queue ADD consultation_id INT DEFAULT NULL');
        
        // Add foreign key constraint
        $this->addSql('ALTER TABLE queue ADD CONSTRAINT FK_7BA7436162FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id)');
        
        // Add index for better performance
        $this->addSql('CREATE INDEX IDX_7BA7436162FF6CDF ON queue (consultation_id)');
    }

    public function down(Schema $schema): void
    {
        // Remove foreign key constraint
        $this->addSql('ALTER TABLE queue DROP FOREIGN KEY FK_7BA7436162FF6CDF');
        
        // Remove index
        $this->addSql('DROP INDEX IDX_7BA7436162FF6CDF ON queue');
        
        // Remove column
        $this->addSql('ALTER TABLE queue DROP consultation_id');
    }
}