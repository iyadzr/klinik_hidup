<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add cost_price and selling_price columns to medication table
 */
final class Version20250122000000_AddPriceFieldsToMedication extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing columns: cost_price and selling_price to medication table, actual_price to prescribed_medication table, and receipt_number to consultation table';
    }

    public function up(Schema $schema): void
    {
        // Add cost_price and selling_price columns to medication table
        $this->addSql('ALTER TABLE medication ADD cost_price NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE medication ADD selling_price NUMERIC(10, 2) DEFAULT NULL');
        
        // Add actual_price column to prescribed_medication table
        $this->addSql('ALTER TABLE prescribed_medication ADD actual_price NUMERIC(10, 2) DEFAULT NULL');
        
        // Add receipt_number column to consultation table
        $this->addSql('ALTER TABLE consultation ADD receipt_number VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove cost_price and selling_price columns from medication table
        $this->addSql('ALTER TABLE medication DROP cost_price');
        $this->addSql('ALTER TABLE medication DROP selling_price');
        
        // Remove actual_price column from prescribed_medication table
        $this->addSql('ALTER TABLE prescribed_medication DROP actual_price');
        
        // Remove receipt_number column from consultation table
        $this->addSql('ALTER TABLE consultation DROP receipt_number');
    }
} 