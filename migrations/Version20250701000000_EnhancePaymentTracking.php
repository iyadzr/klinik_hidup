<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250701000000_EnhancePaymentTracking extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add enhanced payment tracking fields including processed_by, queue, and queue_number';
    }

    public function up(Schema $schema): void
    {
        // Add new columns to payment table
        $this->addSql('ALTER TABLE payment ADD processed_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE payment ADD queue_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD queue_number VARCHAR(10) DEFAULT NULL');
        
        // Add foreign key constraints
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DA2E39599 FOREIGN KEY (processed_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D37FDBD6D FOREIGN KEY (queue_id) REFERENCES queue (id)');
        
        // Create indexes for better performance
        $this->addSql('CREATE INDEX IDX_6D28840DA2E39599 ON payment (processed_by_id)');
        $this->addSql('CREATE INDEX IDX_6D28840D37FDBD6D ON payment (queue_id)');
        $this->addSql('CREATE INDEX IDX_6D28840D_QUEUE_NUMBER ON payment (queue_number)');
        $this->addSql('CREATE INDEX IDX_6D28840D_PAYMENT_DATE ON payment (payment_date)');
    }

    public function down(Schema $schema): void
    {
        // Drop indexes
        $this->addSql('DROP INDEX IDX_6D28840D_PAYMENT_DATE ON payment');
        $this->addSql('DROP INDEX IDX_6D28840D_QUEUE_NUMBER ON payment');
        $this->addSql('DROP INDEX IDX_6D28840D37FDBD6D ON payment');
        $this->addSql('DROP INDEX IDX_6D28840DA2E39599 ON payment');
        
        // Drop foreign key constraints
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DA2E39599');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D37FDBD6D');
        
        // Drop columns
        $this->addSql('ALTER TABLE payment DROP queue_number');
        $this->addSql('ALTER TABLE payment DROP queue_id');
        $this->addSql('ALTER TABLE payment DROP processed_by_id');
    }
} 