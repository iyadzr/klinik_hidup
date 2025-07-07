-- =====================================================
-- API PERFORMANCE FIX - CRITICAL INDEXES
-- =====================================================
-- This script adds critical database indexes to prevent API hanging
-- Run this on existing databases to improve performance

-- Check if indexes already exist before creating them
SET @sql = '';

-- Queue table performance indexes (most critical for API performance)
SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics 
WHERE table_schema = DATABASE() AND table_name = 'queue' AND index_name = 'IDX_QUEUE_DATETIME_STATUS';
SET @sql = IF(@index_exists = 0, 'CREATE INDEX IDX_QUEUE_DATETIME_STATUS ON queue (queue_date_time, status);', '');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics 
WHERE table_schema = DATABASE() AND table_name = 'queue' AND index_name = 'IDX_QUEUE_STATUS_DATETIME';
SET @sql = IF(@index_exists = 0, 'CREATE INDEX IDX_QUEUE_STATUS_DATETIME ON queue (status, queue_date_time);', '');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics 
WHERE table_schema = DATABASE() AND table_name = 'queue' AND index_name = 'IDX_QUEUE_DATETIME_DESC';
SET @sql = IF(@index_exists = 0, 'CREATE INDEX IDX_QUEUE_DATETIME_DESC ON queue (queue_date_time DESC);', '');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics 
WHERE table_schema = DATABASE() AND table_name = 'queue' AND index_name = 'IDX_QUEUE_PATIENT_DOCTOR';
SET @sql = IF(@index_exists = 0, 'CREATE INDEX IDX_QUEUE_PATIENT_DOCTOR ON queue (patient_id, doctor_id);', '');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics 
WHERE table_schema = DATABASE() AND table_name = 'queue' AND index_name = 'IDX_QUEUE_METADATA_SEARCH';
SET @sql = IF(@index_exists = 0, 'CREATE INDEX IDX_QUEUE_METADATA_SEARCH ON queue (metadata(255));', '');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics 
WHERE table_schema = DATABASE() AND table_name = 'queue' AND index_name = 'IDX_QUEUE_PAYMENT_STATUS';
SET @sql = IF(@index_exists = 0, 'CREATE INDEX IDX_QUEUE_PAYMENT_STATUS ON queue (is_paid, payment_method);', '');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Patient table performance indexes
SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics 
WHERE table_schema = DATABASE() AND table_name = 'patient' AND index_name = 'IDX_PATIENT_NAME_SEARCH';
SET @sql = IF(@index_exists = 0, 'CREATE INDEX IDX_PATIENT_NAME_SEARCH ON patient (name(50));', '');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics 
WHERE table_schema = DATABASE() AND table_name = 'patient' AND index_name = 'IDX_PATIENT_NRIC_UNIQUE';
SET @sql = IF(@index_exists = 0, 'CREATE INDEX IDX_PATIENT_NRIC_UNIQUE ON patient (nric);', '');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Consultation table performance indexes
SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics 
WHERE table_schema = DATABASE() AND table_name = 'consultation' AND index_name = 'IDX_CONSULTATION_DATETIME';
SET @sql = IF(@index_exists = 0, 'CREATE INDEX IDX_CONSULTATION_DATETIME ON consultation (consultation_date);', '');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics 
WHERE table_schema = DATABASE() AND table_name = 'consultation' AND index_name = 'IDX_CONSULTATION_PATIENT_DOCTOR';
SET @sql = IF(@index_exists = 0, 'CREATE INDEX IDX_CONSULTATION_PATIENT_DOCTOR ON consultation (patient_id, doctor_id);', '');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Composite indexes for common query patterns
SELECT COUNT(*) INTO @index_exists FROM information_schema.statistics 
WHERE table_schema = DATABASE() AND table_name = 'queue' AND index_name = 'IDX_QUEUE_SEARCH_PATTERN';
SET @sql = IF(@index_exists = 0, 'CREATE INDEX IDX_QUEUE_SEARCH_PATTERN ON queue (queue_date_time, status, patient_id, doctor_id);', '');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Optimize table statistics for better query planning
ANALYZE TABLE queue, patient, consultation, doctor, payment;

-- Show completion message
SELECT 'API Performance Fix Applied Successfully!' as STATUS,
       'Critical database indexes have been added to prevent API hanging.' as MESSAGE,
       'Query performance should be significantly improved.' as RESULT; 