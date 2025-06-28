-- Fix database schema by adding missing columns
-- Run this SQL script to fix the medication search and consultation completion issues

-- Add missing columns to medication table
ALTER TABLE medication ADD COLUMN cost_price DECIMAL(10, 2) DEFAULT NULL;
ALTER TABLE medication ADD COLUMN selling_price DECIMAL(10, 2) DEFAULT NULL;

-- Add missing column to prescribed_medication table
ALTER TABLE prescribed_medication ADD COLUMN actual_price DECIMAL(10, 2) DEFAULT NULL;

-- Add missing column to consultation table
ALTER TABLE consultation ADD COLUMN receipt_number VARCHAR(50) DEFAULT NULL;

-- Update migration table to mark our migration as executed
INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) 
VALUES ('DoctrineMigrations\\Version20250122000000_AddPriceFieldsToMedication', NOW(), 1); 