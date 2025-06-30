-- Fix status column length for consultation and queue tables
-- This fixes the error: Data too long for column 'status' at row 1
-- The status field needs to support 'completed_consultation' which is 22 characters

-- Fix consultation table status column
ALTER TABLE consultation MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending';

-- Fix queue table status column (also uses completed_consultation)
ALTER TABLE queue MODIFY COLUMN status VARCHAR(50) DEFAULT 'waiting';

-- Verify the changes
DESCRIBE consultation;
DESCRIBE queue; 