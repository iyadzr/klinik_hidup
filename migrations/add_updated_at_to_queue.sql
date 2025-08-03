-- Add missing columns to queue table based on Queue entity
-- These columns are needed for proper functionality

-- Check and add columns only if they don't exist
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'queue' AND column_name = 'updated_at' AND table_schema = DATABASE()) = 0,
    'ALTER TABLE queue ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT "updated_at column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'queue' AND column_name = 'metadata' AND table_schema = DATABASE()) = 0,
    'ALTER TABLE queue ADD COLUMN metadata TEXT NULL',
    'SELECT "metadata column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'queue' AND column_name = 'is_paid' AND table_schema = DATABASE()) = 0,
    'ALTER TABLE queue ADD COLUMN is_paid BOOLEAN DEFAULT FALSE',
    'SELECT "is_paid column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'queue' AND column_name = 'paid_at' AND table_schema = DATABASE()) = 0,
    'ALTER TABLE queue ADD COLUMN paid_at DATETIME NULL',
    'SELECT "paid_at column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'queue' AND column_name = 'payment_method' AND table_schema = DATABASE()) = 0,
    'ALTER TABLE queue ADD COLUMN payment_method VARCHAR(20) NULL',
    'SELECT "payment_method column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'queue' AND column_name = 'amount' AND table_schema = DATABASE()) = 0,
    'ALTER TABLE queue ADD COLUMN amount DECIMAL(10,2) NULL',
    'SELECT "amount column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;