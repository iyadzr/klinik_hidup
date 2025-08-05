-- Migration: Add missing payment and metadata columns to queue table
-- This fixes the API error: Unknown column 'q0_.is_paid' in 'field list'

USE clinic_db;

-- Add isPaid column
ALTER TABLE queue ADD COLUMN is_paid BOOLEAN DEFAULT FALSE NOT NULL;

-- Add paidAt column  
ALTER TABLE queue ADD COLUMN paid_at DATETIME NULL;

-- Add paymentMethod column
ALTER TABLE queue ADD COLUMN payment_method VARCHAR(20) NULL;

-- Add amount column
ALTER TABLE queue ADD COLUMN amount DECIMAL(10,2) NULL;

-- Add metadata column
ALTER TABLE queue ADD COLUMN metadata TEXT NULL;

-- Add updatedAt column with auto-update on changes
ALTER TABLE queue ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Update existing records to have proper defaults
UPDATE queue SET is_paid = FALSE WHERE is_paid IS NULL;
UPDATE queue SET updated_at = NOW() WHERE updated_at IS NULL;