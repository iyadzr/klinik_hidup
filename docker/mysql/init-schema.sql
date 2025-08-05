-- Database initialization script for Clinic Management System
-- This script ensures all required columns exist in the database schema
-- It can be run multiple times safely (idempotent)

USE clinic_db;

-- Set SQL mode to be less strict for migration compatibility
SET sql_mode = '';

-- Ensure the queue table has all required columns
-- These ALTER TABLE statements use IF NOT EXISTS equivalent logic

-- Add is_paid column if it doesn't exist
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'queue' 
    AND COLUMN_NAME = 'is_paid'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE queue ADD COLUMN is_paid BOOLEAN DEFAULT FALSE NOT NULL',
    'SELECT "is_paid column already exists" as status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add paid_at column if it doesn't exist
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'queue' 
    AND COLUMN_NAME = 'paid_at'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE queue ADD COLUMN paid_at DATETIME NULL',
    'SELECT "paid_at column already exists" as status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add payment_method column if it doesn't exist
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'queue' 
    AND COLUMN_NAME = 'payment_method'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE queue ADD COLUMN payment_method VARCHAR(20) NULL',
    'SELECT "payment_method column already exists" as status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add amount column if it doesn't exist
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'queue' 
    AND COLUMN_NAME = 'amount'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE queue ADD COLUMN amount DECIMAL(10,2) NULL',
    'SELECT "amount column already exists" as status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add metadata column if it doesn't exist
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'queue' 
    AND COLUMN_NAME = 'metadata'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE queue ADD COLUMN metadata TEXT NULL',
    'SELECT "metadata column already exists" as status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add updated_at column if it doesn't exist
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'queue' 
    AND COLUMN_NAME = 'updated_at'
);

SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE queue ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT "updated_at column already exists" as status'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing records to have proper defaults where needed
UPDATE queue SET is_paid = FALSE WHERE is_paid IS NULL;
UPDATE queue SET updated_at = NOW() WHERE updated_at IS NULL;

-- Reset SQL mode
SET sql_mode = DEFAULT;