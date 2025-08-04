-- =============================================================================
-- COMPREHENSIVE DATABASE DEPLOYMENT SCRIPT
-- =============================================================================
-- This script safely deploys database schema changes with proper error handling
-- and checks to prevent duplicate operations
-- =============================================================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS clinic_db;
USE clinic_db;

-- Set proper SQL mode and timezone
SET sql_mode = 'TRADITIONAL';
SET time_zone = '+08:00';

-- =============================================================================
-- 1. FIX INVALID DATETIME VALUES
-- =============================================================================

-- Fix invalid datetime values in medications table if it exists
SET @table_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'medications'
);

SET @fix_medications = IF(@table_exists > 0, 'UPDATE medications SET last_updated = CURRENT_TIMESTAMP WHERE last_updated = "0000-00-00 00:00:00" OR last_updated IS NULL', 'SELECT "medications table does not exist yet" as message');
PREPARE stmt FROM @fix_medications;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix invalid datetime values in other tables that might exist
SET @tables_to_fix = 'users,doctors,patients,consultations,queue,prescribed_medications,payments';

-- Fix users table
SET @table_exists = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'clinic_db' AND TABLE_NAME = 'users');
SET @fix_query = IF(@table_exists > 0, 
    'UPDATE users SET created_at = CURRENT_TIMESTAMP WHERE created_at = "0000-00-00 00:00:00" OR created_at IS NULL',
    'SELECT "users table does not exist yet" as message'
);
PREPARE stmt FROM @fix_query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix doctors table
SET @table_exists = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'clinic_db' AND TABLE_NAME = 'doctors');
SET @fix_query = IF(@table_exists > 0, 
    'UPDATE doctors SET created_at = CURRENT_TIMESTAMP WHERE created_at = "0000-00-00 00:00:00" OR created_at IS NULL',
    'SELECT "doctors table does not exist yet" as message'
);
PREPARE stmt FROM @fix_query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 2. ADD MISSING COLUMNS TO EXISTING TABLES
-- =============================================================================

-- Add updated_at column to queue table if missing
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'queue' 
    AND COLUMN_NAME = 'updated_at'
);

SET @add_column = IF(@column_exists = 0, 
    'ALTER TABLE queue ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT "updated_at column already exists in queue table" as message'
);
PREPARE stmt FROM @add_column;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add consultation_id column to queue table if missing
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'queue' 
    AND COLUMN_NAME = 'consultation_id'
);

SET @add_column = IF(@column_exists = 0, 
    'ALTER TABLE queue ADD COLUMN consultation_id INT DEFAULT NULL',
    'SELECT "consultation_id column already exists in queue table" as message'
);
PREPARE stmt FROM @add_column;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add is_paid column to queue table if missing
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'queue' 
    AND COLUMN_NAME = 'is_paid'
);

SET @add_column = IF(@column_exists = 0, 
    'ALTER TABLE queue ADD COLUMN is_paid BOOLEAN DEFAULT FALSE',
    'SELECT "is_paid column already exists in queue table" as message'
);
PREPARE stmt FROM @add_column;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add paid_at column to queue table if missing
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'queue' 
    AND COLUMN_NAME = 'paid_at'
);

SET @add_column = IF(@column_exists = 0, 
    'ALTER TABLE queue ADD COLUMN paid_at DATETIME NULL',
    'SELECT "paid_at column already exists in queue table" as message'
);
PREPARE stmt FROM @add_column;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add payment_method column to queue table if missing
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'queue' 
    AND COLUMN_NAME = 'payment_method'
);

SET @add_column = IF(@column_exists = 0, 
    'ALTER TABLE queue ADD COLUMN payment_method VARCHAR(20) NULL',
    'SELECT "payment_method column already exists in queue table" as message'
);
PREPARE stmt FROM @add_column;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add amount column to queue table if missing
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'queue' 
    AND COLUMN_NAME = 'amount'
);

SET @add_column = IF(@column_exists = 0, 
    'ALTER TABLE queue ADD COLUMN amount DECIMAL(10,2) NULL',
    'SELECT "amount column already exists in queue table" as message'
);
PREPARE stmt FROM @add_column;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add metadata column to queue table if missing
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'queue' 
    AND COLUMN_NAME = 'metadata'
);

SET @add_column = IF(@column_exists = 0, 
    'ALTER TABLE queue ADD COLUMN metadata TEXT NULL',
    'SELECT "metadata column already exists in queue table" as message'
);
PREPARE stmt FROM @add_column;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 3. CREATE MISSING INDEXES FOR PERFORMANCE
-- =============================================================================

-- Add index on queue.updated_at if not exists
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'queue' 
    AND INDEX_NAME = 'idx_queue_updated_at'
);

SET @add_index = IF(@index_exists = 0 AND (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'clinic_db' AND TABLE_NAME = 'queue') > 0,
    'ALTER TABLE queue ADD INDEX idx_queue_updated_at (updated_at)',
    'SELECT "idx_queue_updated_at index already exists or queue table does not exist" as message'
);
PREPARE stmt FROM @add_index;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index on queue.consultation_id if not exists
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'queue' 
    AND INDEX_NAME = 'idx_queue_consultation_id'
);

SET @add_index = IF(@index_exists = 0 AND (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'clinic_db' AND TABLE_NAME = 'queue') > 0,
    'ALTER TABLE queue ADD INDEX idx_queue_consultation_id (consultation_id)',
    'SELECT "idx_queue_consultation_id index already exists or queue table does not exist" as message'
);
PREPARE stmt FROM @add_index;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 4. CREATE DEFAULT ADMIN USER IF NOT EXISTS
-- =============================================================================

-- Create superadmin user if not exists
SET @user_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLES t1
    CROSS JOIN (
        SELECT COUNT(*) as user_count
        FROM users 
        WHERE username = 'superadmin'
    ) t2
    WHERE t1.TABLE_SCHEMA = 'clinic_db'
    AND t1.TABLE_NAME = 'users'
    AND t2.user_count > 0
);

SET @create_user = IF(@user_exists = 0 AND (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'clinic_db' AND TABLE_NAME = 'users') > 0,
    CONCAT('INSERT INTO users (username, email, name, password, roles, created_at) VALUES ',
           '("superadmin", "admin@clinic.com", "Super Administrator", ',
           '"$2y$13$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy", ',
           '"[\"ROLE_SUPER_ADMIN\"]", CURRENT_TIMESTAMP) ',
           'ON DUPLICATE KEY UPDATE password = "$2y$13$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy"'),
    'SELECT "superadmin user already exists or users table does not exist" as message'
);
PREPARE stmt FROM @create_user;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 5. UPDATE ENTITY MAPPINGS CONSISTENCY
-- =============================================================================

-- Fix User-Doctor relationship mapping if tables exist
SET @users_exists = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'clinic_db' AND TABLE_NAME = 'users');
SET @doctors_exists = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'clinic_db' AND TABLE_NAME = 'doctors');

-- Add user_id column to doctors table if missing and tables exist
SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'clinic_db' 
    AND TABLE_NAME = 'doctors' 
    AND COLUMN_NAME = 'user_id'
);

SET @add_user_id = IF(@column_exists = 0 AND @doctors_exists > 0,
    'ALTER TABLE doctors ADD COLUMN user_id INT NULL, ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "user_id column already exists in doctors table or doctors table does not exist" as message'
);
PREPARE stmt FROM @add_user_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 6. CLEANUP AND OPTIMIZATION
-- =============================================================================

-- Update all NULL datetime fields to current timestamp for consistency
SET @update_nulls = 'UPDATE medications SET last_updated = CURRENT_TIMESTAMP WHERE last_updated IS NULL';
SET @medications_exists = (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'clinic_db' AND TABLE_NAME = 'medications');
SET @execute_update = IF(@medications_exists > 0, @update_nulls, 'SELECT "medications table does not exist" as message');
PREPARE stmt FROM @execute_update;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 7. VERIFICATION AND SUMMARY
-- =============================================================================

-- Show summary of tables and their status
SELECT 
    'DATABASE DEPLOYMENT SUMMARY' as message,
    (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'clinic_db') as total_tables,
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'clinic_db') as total_columns,
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'clinic_db') as total_indexes;

-- Show queue table structure if it exists
SET @show_queue = IF(
    (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'clinic_db' AND TABLE_NAME = 'queue') > 0,
    'SELECT "Queue table columns:" as message; DESCRIBE queue',
    'SELECT "Queue table does not exist yet" as message'
);
PREPARE stmt FROM @show_queue;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show users count
SET @show_users = IF(
    (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'clinic_db' AND TABLE_NAME = 'users') > 0,
    'SELECT COUNT(*) as total_users FROM users',
    'SELECT "Users table does not exist yet" as message'
);
PREPARE stmt FROM @show_users;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'DATABASE DEPLOYMENT COMPLETED SUCCESSFULLY!' as final_message;