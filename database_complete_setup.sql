-- =====================================================
-- CLINIC MANAGEMENT SYSTEM - COMPLETE DATABASE SETUP
-- =====================================================
-- This script creates the complete database structure
-- from the beginning of the project to current state
-- Run this script with clinic_db database

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS clinic_db;
USE clinic_db;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if they exist (for clean install)
DROP TABLE IF EXISTS prescribed_medication;
DROP TABLE IF EXISTS payment;
DROP TABLE IF EXISTS medical_certificate;
DROP TABLE IF EXISTS consultation;
DROP TABLE IF EXISTS appointment;
DROP TABLE IF EXISTS queue;
DROP TABLE IF EXISTS patient;
DROP TABLE IF EXISTS doctor;
DROP TABLE IF EXISTS clinic_assistant;
DROP TABLE IF EXISTS medication;
DROP TABLE IF EXISTS setting;
DROP TABLE IF EXISTS user;
DROP TABLE IF EXISTS receipt_counter;
DROP TABLE IF EXISTS doctrine_migration_versions;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- User table (authentication and roles)
CREATE TABLE user (
    id INT AUTO_INCREMENT NOT NULL,
    username VARCHAR(180) NOT NULL,
    email VARCHAR(180) NOT NULL,
    roles JSON NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    allowed_pages JSON DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE INDEX UNIQ_8D93D649F85E0677 (username),
    UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Doctor table (medical profiles linked to users)
CREATE TABLE doctor (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    specialization VARCHAR(255) NOT NULL,
    license_number VARCHAR(255) DEFAULT NULL,
    working_hours JSON NOT NULL,
    UNIQUE INDEX UNIQ_1FC0F36AA76ED395 (user_id),
    INDEX IDX_1FC0F36A_EMAIL (email),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Clinic Assistant table
CREATE TABLE clinic_assistant (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    UNIQUE INDEX UNIQ_CA_USERNAME (username),
    UNIQUE INDEX UNIQ_CA_EMAIL (email),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Patient table
CREATE TABLE patient (
    id INT AUTO_INCREMENT NOT NULL,
    registered_by_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    nric VARCHAR(20) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) NOT NULL,
    date_of_birth DATE NOT NULL,
    medical_history VARCHAR(1000) DEFAULT NULL,
    company VARCHAR(255) DEFAULT NULL,
    remarks VARCHAR(1000) DEFAULT NULL,
    gender VARCHAR(1) DEFAULT NULL,
    address VARCHAR(500) DEFAULT NULL,
    UNIQUE INDEX UNIQ_1ADAD7EBCD4C031E (nric),
    INDEX IDX_1ADAD7EB27E92E18 (registered_by_id),
    INDEX IDX_PATIENT_NAME (name),
    INDEX IDX_PATIENT_PHONE (phone),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Medication table
CREATE TABLE medication (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(255) NOT NULL,
    unit_type VARCHAR(50) NOT NULL,
    unit_description VARCHAR(100) DEFAULT NULL,
    description VARCHAR(500) DEFAULT NULL,
    category VARCHAR(100) DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX IDX_MEDICATION_NAME (name),
    INDEX IDX_MEDICATION_CATEGORY (category),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- =====================================================
-- OPERATIONAL TABLES
-- =====================================================

-- Queue table
CREATE TABLE queue (
    id INT AUTO_INCREMENT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    queue_date_time DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    status VARCHAR(20) NOT NULL DEFAULT 'waiting',
    queue_number VARCHAR(20) DEFAULT NULL,
    registration_number INT NOT NULL,
    metadata LONGTEXT DEFAULT NULL,
    INDEX IDX_7FFD7F636B899279 (patient_id),
    INDEX IDX_7FFD7F6387F4FB17 (doctor_id),
    INDEX IDX_QUEUE_DATE (queue_date_time),
    INDEX IDX_QUEUE_STATUS (status),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Appointment table
CREATE TABLE appointment (
    id INT AUTO_INCREMENT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date_time DATETIME NOT NULL,
    reason VARCHAR(1000) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
    notes VARCHAR(1000) DEFAULT NULL,
    INDEX IDX_FE38F8446B899279 (patient_id),
    INDEX IDX_FE38F84487F4FB17 (doctor_id),
    INDEX IDX_APPOINTMENT_DATE (appointment_date_time),
    INDEX IDX_APPOINTMENT_STATUS (status),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Consultation table
CREATE TABLE consultation (
    id INT AUTO_INCREMENT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    consultation_date DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    diagnosis LONGTEXT NOT NULL,
    medications LONGTEXT NOT NULL,
    symptoms LONGTEXT DEFAULT NULL,
    treatment LONGTEXT DEFAULT NULL,
    notes LONGTEXT DEFAULT NULL,
    follow_up_plan LONGTEXT DEFAULT NULL,
    consultation_fee NUMERIC(10, 2) DEFAULT NULL,
    medicines_fee NUMERIC(10, 2) DEFAULT NULL,
    total_amount NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    is_paid TINYINT(1) NOT NULL DEFAULT 0,
    paid_at DATETIME DEFAULT NULL,
    receipt_number VARCHAR(50) DEFAULT NULL,
    has_medical_certificate TINYINT(1) DEFAULT NULL,
    mc_start_date DATETIME DEFAULT NULL,
    mc_end_date DATETIME DEFAULT NULL,
    mc_number VARCHAR(255) DEFAULT NULL,
    mc_running_number VARCHAR(255) DEFAULT NULL,
    INDEX IDX_964685A66B899279 (patient_id),
    INDEX IDX_964685A687F4FB17 (doctor_id),
    INDEX IDX_CONSULTATION_DATE (consultation_date),
    INDEX IDX_CONSULTATION_PAID (is_paid),
    UNIQUE INDEX UNIQ_RECEIPT_NUMBER (receipt_number),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Payment table
CREATE TABLE payment (
    id INT AUTO_INCREMENT NOT NULL,
    consultation_id INT NOT NULL,
    amount DOUBLE PRECISION NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    payment_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    reference VARCHAR(255) DEFAULT NULL,
    notes VARCHAR(1000) DEFAULT NULL,
    INDEX IDX_6D28840D62FF6CDF (consultation_id),
    INDEX IDX_PAYMENT_DATE (payment_date),
    INDEX IDX_PAYMENT_METHOD (payment_method),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Prescribed Medication table
CREATE TABLE prescribed_medication (
    id INT AUTO_INCREMENT NOT NULL,
    consultation_id INT NOT NULL,
    medication_id INT NOT NULL,
    quantity INT NOT NULL,
    instructions VARCHAR(500) DEFAULT NULL,
    prescribed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX IDX_51C16B7062FF6CDF (consultation_id),
    INDEX IDX_51C16B702C4DE6DA (medication_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Medical Certificate table
CREATE TABLE medical_certificate (
    id INT AUTO_INCREMENT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    issue_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    start_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    end_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    diagnosis VARCHAR(255) NOT NULL,
    remarks VARCHAR(1000) DEFAULT NULL,
    certificate_number VARCHAR(50) NOT NULL,
    INDEX IDX_B36515F86B899279 (patient_id),
    INDEX IDX_B36515F887F4FB17 (doctor_id),
    UNIQUE INDEX UNIQ_CERTIFICATE_NUMBER (certificate_number),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- =====================================================
-- SYSTEM TABLES
-- =====================================================

-- Settings table
CREATE TABLE setting (
    id INT AUTO_INCREMENT NOT NULL,
    setting_key VARCHAR(255) NOT NULL,
    setting_value LONGTEXT DEFAULT NULL,
    category VARCHAR(100) DEFAULT NULL,
    description VARCHAR(500) DEFAULT NULL,
    value_type VARCHAR(50) NOT NULL DEFAULT 'string',
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE INDEX UNIQ_9F74B8985FA1E697 (setting_key),
    INDEX IDX_SETTING_CATEGORY (category),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Receipt Counter table (for receipt numbering)
CREATE TABLE receipt_counter (
    id INT AUTO_INCREMENT NOT NULL,
    current_number INT NOT NULL DEFAULT 1,
    prefix VARCHAR(10) DEFAULT 'RC',
    date_created DATE NOT NULL,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Doctrine migrations table
CREATE TABLE doctrine_migration_versions (
    version VARCHAR(191) NOT NULL,
    executed_at DATETIME DEFAULT NULL,
    execution_time INT DEFAULT NULL,
    PRIMARY KEY(version)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- =====================================================
-- FOREIGN KEY CONSTRAINTS
-- =====================================================

-- Doctor -> User relationship
ALTER TABLE doctor 
ADD CONSTRAINT FK_1FC0F36AA76ED395 
FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;

-- Patient -> Clinic Assistant relationship
ALTER TABLE patient 
ADD CONSTRAINT FK_1ADAD7EB27E92E18 
FOREIGN KEY (registered_by_id) REFERENCES clinic_assistant (id) ON DELETE SET NULL;

-- Queue relationships
ALTER TABLE queue 
ADD CONSTRAINT FK_7FFD7F636B899279 
FOREIGN KEY (patient_id) REFERENCES patient (id) ON DELETE CASCADE;

ALTER TABLE queue 
ADD CONSTRAINT FK_7FFD7F6387F4FB17 
FOREIGN KEY (doctor_id) REFERENCES doctor (id) ON DELETE CASCADE;

-- Appointment relationships
ALTER TABLE appointment 
ADD CONSTRAINT FK_FE38F8446B899279 
FOREIGN KEY (patient_id) REFERENCES patient (id) ON DELETE CASCADE;

ALTER TABLE appointment 
ADD CONSTRAINT FK_FE38F84487F4FB17 
FOREIGN KEY (doctor_id) REFERENCES doctor (id) ON DELETE CASCADE;

-- Consultation relationships
ALTER TABLE consultation 
ADD CONSTRAINT FK_964685A66B899279 
FOREIGN KEY (patient_id) REFERENCES patient (id) ON DELETE CASCADE;

ALTER TABLE consultation 
ADD CONSTRAINT FK_964685A687F4FB17 
FOREIGN KEY (doctor_id) REFERENCES doctor (id) ON DELETE CASCADE;

-- Payment relationships
ALTER TABLE payment 
ADD CONSTRAINT FK_6D28840D62FF6CDF 
FOREIGN KEY (consultation_id) REFERENCES consultation (id) ON DELETE CASCADE;

-- Prescribed medication relationships
ALTER TABLE prescribed_medication 
ADD CONSTRAINT FK_51C16B7062FF6CDF 
FOREIGN KEY (consultation_id) REFERENCES consultation (id) ON DELETE CASCADE;

ALTER TABLE prescribed_medication 
ADD CONSTRAINT FK_51C16B702C4DE6DA 
FOREIGN KEY (medication_id) REFERENCES medication (id) ON DELETE CASCADE;

-- Medical certificate relationships
ALTER TABLE medical_certificate 
ADD CONSTRAINT FK_B36515F86B899279 
FOREIGN KEY (patient_id) REFERENCES patient (id) ON DELETE CASCADE;

ALTER TABLE medical_certificate 
ADD CONSTRAINT FK_B36515F887F4FB17 
FOREIGN KEY (doctor_id) REFERENCES doctor (id) ON DELETE CASCADE;

-- =====================================================
-- INITIAL DATA
-- =====================================================

-- Insert migration records to mark all migrations as executed
INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES 
('DoctrineMigrations\\Version20250611092531', NOW(), 100),
('DoctrineMigrations\\Version20250611182520', NOW(), 50),
('DoctrineMigrations\\Version20250611184151', NOW(), 25),
('DoctrineMigrations\\Version20250612044643', NOW(), 30),
('DoctrineMigrations\\Version20250617175216', NOW(), 40),
('DoctrineMigrations\\Version20250123000000', NOW(), 35);

-- Insert initial receipt counter
INSERT INTO receipt_counter (current_number, prefix, date_created) VALUES 
(1, 'RC', CURDATE());

-- Insert default system settings
INSERT INTO setting (setting_key, setting_value, category, description, value_type, created_at, updated_at, is_system) VALUES 
('clinic_name', 'Klinik HiDUP sihat', 'general', 'Name of the clinic', 'string', NOW(), NOW(), 1),
('clinic_address', '', 'general', 'Clinic address', 'string', NOW(), NOW(), 1),
('clinic_phone', '', 'general', 'Clinic phone number', 'string', NOW(), NOW(), 1),
('consultation_fee', '50.00', 'billing', 'Default consultation fee', 'number', NOW(), NOW(), 1),
('timezone', 'Asia/Kuala_Lumpur', 'system', 'System timezone', 'string', NOW(), NOW(), 1);

-- Insert default super admin user
INSERT INTO user (username, email, roles, password, name, allowed_pages, created_at, updated_at, is_active) VALUES 
('admin', 'admin@clinic.com', JSON_ARRAY('ROLE_USER', 'ROLE_SUPER_ADMIN'), '$2y$13$dummy.hash.for.temp.password', 'System Administrator', JSON_ARRAY('dashboard', 'users', 'doctors', 'patients', 'consultations', 'queue', 'financial', 'settings'), NOW(), NOW(), 1);

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Additional performance indexes
CREATE INDEX IDX_USER_ACTIVE ON user (is_active);
CREATE INDEX IDX_USER_ROLES ON user ((CAST(roles AS CHAR(255))));
CREATE INDEX IDX_DOCTOR_SPECIALIZATION ON doctor (specialization);
CREATE INDEX IDX_PATIENT_DOB ON patient (date_of_birth);
CREATE INDEX IDX_CONSULTATION_CREATED ON consultation (created_at);
CREATE INDEX IDX_QUEUE_REG_NUMBER ON queue (registration_number);

-- =====================================================
-- CRITICAL PERFORMANCE INDEXES FOR API OPTIMIZATION
-- =====================================================

-- Queue table performance indexes (most critical for API performance)
CREATE INDEX IDX_QUEUE_DATETIME_STATUS ON queue (queue_date_time, status);
CREATE INDEX IDX_QUEUE_STATUS_DATETIME ON queue (status, queue_date_time);
CREATE INDEX IDX_QUEUE_DATETIME_DESC ON queue (queue_date_time DESC);
CREATE INDEX IDX_QUEUE_PATIENT_DOCTOR ON queue (patient_id, doctor_id);
CREATE INDEX IDX_QUEUE_METADATA_SEARCH ON queue (metadata(255)); -- For group consultation lookups
CREATE INDEX IDX_QUEUE_PAYMENT_STATUS ON queue (is_paid, payment_method);

-- Patient table performance indexes
CREATE INDEX IDX_PATIENT_NAME_SEARCH ON patient (name(50));
CREATE INDEX IDX_PATIENT_NRIC_UNIQUE ON patient (nric);
CREATE INDEX IDX_PATIENT_PHONE ON patient (phone);
CREATE INDEX IDX_PATIENT_CREATED ON patient (created_at);

-- Consultation table performance indexes
CREATE INDEX IDX_CONSULTATION_DATETIME ON consultation (consultation_date);
CREATE INDEX IDX_CONSULTATION_PATIENT_DOCTOR ON consultation (patient_id, doctor_id);
CREATE INDEX IDX_CONSULTATION_STATUS_DATE ON consultation (status, consultation_date);
CREATE INDEX IDX_CONSULTATION_TOTAL_AMOUNT ON consultation (total_amount);

-- Doctor table performance indexes
CREATE INDEX IDX_DOCTOR_NAME ON doctor (name);
CREATE INDEX IDX_DOCTOR_ACTIVE ON doctor (is_active);

-- Payment table performance indexes
CREATE INDEX IDX_PAYMENT_DATE_METHOD ON payment (payment_date, payment_method);
CREATE INDEX IDX_PAYMENT_CONSULTATION ON payment (consultation_id);
CREATE INDEX IDX_PAYMENT_AMOUNT ON payment (amount);

-- Prescribed medication performance indexes
CREATE INDEX IDX_PRESCRIBED_MED_CONSULTATION ON prescribed_medication (consultation_id);
CREATE INDEX IDX_PRESCRIBED_MED_MEDICATION ON prescribed_medication (medication_id);

-- Medical certificate performance indexes
CREATE INDEX IDX_MEDICAL_CERT_CONSULTATION ON medical_certificate (consultation_id);
CREATE INDEX IDX_MEDICAL_CERT_DATE ON medical_certificate (issue_date);

-- Composite indexes for common query patterns
CREATE INDEX IDX_QUEUE_SEARCH_PATTERN ON queue (queue_date_time, status, patient_id, doctor_id);
CREATE INDEX IDX_CONSULTATION_SEARCH_PATTERN ON consultation (consultation_date, status, patient_id);

-- =====================================================
-- DATABASE PERFORMANCE OPTIMIZATION SETTINGS
-- =====================================================

-- Optimize MySQL settings for better performance
SET GLOBAL innodb_buffer_pool_size = 268435456; -- 256MB
SET GLOBAL query_cache_size = 33554432; -- 32MB
SET GLOBAL query_cache_type = 1;
SET GLOBAL slow_query_log = 1;
SET GLOBAL long_query_time = 2;
SET GLOBAL max_connections = 200;
SET GLOBAL wait_timeout = 300;
SET GLOBAL interactive_timeout = 300;

-- Enable query cache for better performance
SET GLOBAL query_cache_limit = 1048576; -- 1MB
SET GLOBAL query_cache_min_res_unit = 4096;

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================

SELECT 'Database setup completed successfully!' as STATUS,
       'All tables, relationships, and initial data have been created.' as MESSAGE,
       'Performance indexes have been optimized for API stability.' as PERFORMANCE,
       'Default admin login: admin@clinic.com / password: temp123456' as ADMIN_LOGIN; 