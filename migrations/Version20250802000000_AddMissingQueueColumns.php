<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250802000000_AddMissingQueueColumns extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing columns to queue table for payment and consultation tracking';
    }

    public function up(Schema $schema): void
    {
        // Check if columns exist before adding them
        $this->addSql('
            SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
                          WHERE table_schema = DATABASE() AND table_name = "queue" AND column_name = "is_paid");
            SET @sqlstmt := IF(@exist = 0, "ALTER TABLE queue ADD COLUMN is_paid TINYINT(1) DEFAULT 0 NOT NULL", "SELECT 1");
            PREPARE stmt FROM @sqlstmt;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ');

        $this->addSql('
            SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
                          WHERE table_schema = DATABASE() AND table_name = "queue" AND column_name = "paid_at");
            SET @sqlstmt := IF(@exist = 0, "ALTER TABLE queue ADD COLUMN paid_at DATETIME DEFAULT NULL", "SELECT 1");
            PREPARE stmt FROM @sqlstmt;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ');

        $this->addSql('
            SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
                          WHERE table_schema = DATABASE() AND table_name = "queue" AND column_name = "payment_method");
            SET @sqlstmt := IF(@exist = 0, "ALTER TABLE queue ADD COLUMN payment_method VARCHAR(20) DEFAULT NULL", "SELECT 1");
            PREPARE stmt FROM @sqlstmt;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ');

        $this->addSql('
            SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
                          WHERE table_schema = DATABASE() AND table_name = "queue" AND column_name = "amount");
            SET @sqlstmt := IF(@exist = 0, "ALTER TABLE queue ADD COLUMN amount DECIMAL(10,2) DEFAULT NULL", "SELECT 1");
            PREPARE stmt FROM @sqlstmt;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ');

        $this->addSql('
            SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
                          WHERE table_schema = DATABASE() AND table_name = "queue" AND column_name = "consultation_id");
            SET @sqlstmt := IF(@exist = 0, "ALTER TABLE queue ADD COLUMN consultation_id INT DEFAULT NULL", "SELECT 1");
            PREPARE stmt FROM @sqlstmt;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ');

        // Update status column length if needed
        $this->addSql('
            SET @exist := (SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns 
                          WHERE table_schema = DATABASE() AND table_name = "queue" AND column_name = "status");
            SET @sqlstmt := IF(@exist < 50, "ALTER TABLE queue MODIFY status VARCHAR(50)", "SELECT 1");
            PREPARE stmt FROM @sqlstmt;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ');

        // Add metadata column if missing
        $this->addSql('
            SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
                          WHERE table_schema = DATABASE() AND table_name = "queue" AND column_name = "metadata");
            SET @sqlstmt := IF(@exist = 0, "ALTER TABLE queue ADD COLUMN metadata LONGTEXT DEFAULT NULL", "SELECT 1");
            PREPARE stmt FROM @sqlstmt;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ');

        // Add updated_at column if missing
        $this->addSql('
            SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
                          WHERE table_schema = DATABASE() AND table_name = "queue" AND column_name = "updated_at");
            SET @sqlstmt := IF(@exist = 0, "ALTER TABLE queue ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", "SELECT 1");
            PREPARE stmt FROM @sqlstmt;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ');

        // Add foreign key constraint if it doesn't exist
        $this->addSql('
            SET @exist := (SELECT COUNT(*) FROM information_schema.table_constraints 
                          WHERE table_schema = DATABASE() AND table_name = "queue" AND constraint_name = "FK_7FFD7F6362FF6CDF");
            SET @sqlstmt := IF(@exist = 0, "ALTER TABLE queue ADD CONSTRAINT FK_7FFD7F6362FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id)", "SELECT 1");
            PREPARE stmt FROM @sqlstmt;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ');

        // Add index for consultation_id if it doesn't exist
        $this->addSql('
            SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
                          WHERE table_schema = DATABASE() AND table_name = "queue" AND index_name = "IDX_7FFD7F6362FF6CDF");
            SET @sqlstmt := IF(@exist = 0, "CREATE INDEX IDX_7FFD7F6362FF6CDF ON queue (consultation_id)", "SELECT 1");
            PREPARE stmt FROM @sqlstmt;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ');
    }

    public function down(Schema $schema): void
    {
        // Remove foreign key constraint
        $this->addSql('ALTER TABLE queue DROP FOREIGN KEY IF EXISTS FK_7FFD7F6362FF6CDF');
        
        // Remove index
        $this->addSql('DROP INDEX IF EXISTS IDX_7FFD7F6362FF6CDF ON queue');
        
        // Remove columns (be careful in production!)
        $this->addSql('ALTER TABLE queue DROP COLUMN IF EXISTS consultation_id');
        $this->addSql('ALTER TABLE queue DROP COLUMN IF EXISTS amount');
        $this->addSql('ALTER TABLE queue DROP COLUMN IF EXISTS payment_method');
        $this->addSql('ALTER TABLE queue DROP COLUMN IF EXISTS paid_at');
        $this->addSql('ALTER TABLE queue DROP COLUMN IF EXISTS is_paid');
    }
}