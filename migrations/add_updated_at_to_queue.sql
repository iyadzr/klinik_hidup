-- Add updated_at column to queue table for SSE functionality
-- This column is needed to track recent changes for real-time updates

ALTER TABLE queue 
ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;