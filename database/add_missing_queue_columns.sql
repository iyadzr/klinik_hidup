-- Add missing columns to queue table
-- Run this if you prefer manual SQL execution

-- Add is_paid column if not exists
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
              WHERE table_schema = DATABASE() AND table_name = 'queue' AND column_name = 'is_paid');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE queue ADD COLUMN is_paid TINYINT(1) DEFAULT 0 NOT NULL', 'SELECT "is_paid column already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add paid_at column if not exists
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
              WHERE table_schema = DATABASE() AND table_name = 'queue' AND column_name = 'paid_at');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE queue ADD COLUMN paid_at DATETIME DEFAULT NULL', 'SELECT "paid_at column already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add payment_method column if not exists
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
              WHERE table_schema = DATABASE() AND table_name = 'queue' AND column_name = 'payment_method');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE queue ADD COLUMN payment_method VARCHAR(20) DEFAULT NULL', 'SELECT "payment_method column already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add amount column if not exists
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
              WHERE table_schema = DATABASE() AND table_name = 'queue' AND column_name = 'amount');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE queue ADD COLUMN amount DECIMAL(10,2) DEFAULT NULL', 'SELECT "amount column already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add consultation_id column if not exists
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
              WHERE table_schema = DATABASE() AND table_name = 'queue' AND column_name = 'consultation_id');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE queue ADD COLUMN consultation_id INT DEFAULT NULL', 'SELECT "consultation_id column already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update status column length if needed
SET @exist := (SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns 
              WHERE table_schema = DATABASE() AND table_name = 'queue' AND column_name = 'status');
SET @sqlstmt := IF(@exist < 50, 'ALTER TABLE queue MODIFY status VARCHAR(50)', 'SELECT "status column already correct length"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add metadata column if not exists
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
              WHERE table_schema = DATABASE() AND table_name = 'queue' AND column_name = 'metadata');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE queue ADD COLUMN metadata LONGTEXT DEFAULT NULL', 'SELECT "metadata column already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add updated_at column if not exists
SET @exist := (SELECT COUNT(*) FROM information_schema.columns 
              WHERE table_schema = DATABASE() AND table_name = 'queue' AND column_name = 'updated_at');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE queue ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT "updated_at column already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint if not exists
SET @exist := (SELECT COUNT(*) FROM information_schema.table_constraints 
              WHERE table_schema = DATABASE() AND table_name = 'queue' AND constraint_name = 'FK_7FFD7F6362FF6CDF');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE queue ADD CONSTRAINT FK_7FFD7F6362FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id)', 'SELECT "Foreign key already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index if not exists
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
              WHERE table_schema = DATABASE() AND table_name = 'queue' AND index_name = 'IDX_7FFD7F6362FF6CDF');
SET @sqlstmt := IF(@exist = 0, 'CREATE INDEX IDX_7FFD7F6362FF6CDF ON queue (consultation_id)', 'SELECT "Index already exists"');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Queue table migration completed successfully!' as status;