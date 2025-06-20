<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250119000000_AddPaymentFieldsToQueue extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment fields and medication pricing fields';
    }

    public function up(Schema $schema): void
    {
        // Add price fields to medication table
        $this->addSql('ALTER TABLE medication ADD cost_price NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE medication ADD selling_price NUMERIC(10, 2) DEFAULT NULL');
        
        // Add actual price field to prescribed_medication table
        $this->addSql('ALTER TABLE prescribed_medication ADD actual_price NUMERIC(10, 2) DEFAULT NULL');
        
        // Original queue payment fields
        $this->addSql('ALTER TABLE queue ADD payment_method VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE queue ADD payment_status VARCHAR(20) DEFAULT \'unpaid\'');
        $this->addSql('ALTER TABLE queue ADD amount_paid NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE queue ADD paid_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove price fields from medication table
        $this->addSql('ALTER TABLE medication DROP cost_price');
        $this->addSql('ALTER TABLE medication DROP selling_price');
        
        // Remove actual price field from prescribed_medication table
        $this->addSql('ALTER TABLE prescribed_medication DROP actual_price');
        
        // Original queue payment fields rollback
        $this->addSql('ALTER TABLE queue DROP payment_method');
        $this->addSql('ALTER TABLE queue DROP payment_status');
        $this->addSql('ALTER TABLE queue DROP amount_paid');
        $this->addSql('ALTER TABLE queue DROP paid_at');
    }
} 