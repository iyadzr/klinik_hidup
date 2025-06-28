-- =====================================================
-- TEST DATABASE SETUP FOR CLINIC MANAGEMENT SYSTEM
-- =====================================================
-- This script creates the test database and grants permissions
-- Run this with MySQL root user to set up test environment

-- Create test database
CREATE DATABASE IF NOT EXISTS clinic_db_test;

-- Grant all privileges to clinic_user for test database
GRANT ALL PRIVILEGES ON clinic_db_test.* TO 'clinic_user'@'%';
FLUSH PRIVILEGES;

-- Use the test database
USE clinic_db_test;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop all tables for clean install (if they exist)
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
-- TABLES CREATION
-- =====================================================

-- Create User table
CREATE TABLE user (
    id INT AUTO_INCREMENT NOT NULL,
    username VARCHAR(180) NOT NULL,
    roles JSON NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE INDEX UNIQ_8D93D649F85E0677 (username),
    UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Create Doctor table
CREATE TABLE doctor (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    license_number VARCHAR(50) NOT NULL,
    consultation_fee NUMERIC(10, 2) NOT NULL,
    years_of_experience INT DEFAULT NULL,
    qualifications TEXT DEFAULT NULL,
    available_days VARCHAR(100) DEFAULT NULL,
    available_hours VARCHAR(100) DEFAULT NULL,
    is_available TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE INDEX UNIQ_1FC0F36AA76ED395 (user_id),
    UNIQUE INDEX UNIQ_1FC0F36A3B5A08F7 (license_number),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Create Clinic Assistant table
CREATE TABLE clinic_assistant (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    position VARCHAR(100) DEFAULT NULL,
    hire_date DATE DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE INDEX UNIQ_7B2C0ECDA76ED395 (user_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Create Patient table
CREATE TABLE patient (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    date_of_birth DATE NOT NULL,
    gender VARCHAR(10) NOT NULL,
    emergency_contact_name VARCHAR(100) DEFAULT NULL,
    emergency_contact_phone VARCHAR(20) DEFAULT NULL,
    medical_history TEXT DEFAULT NULL,
    allergies TEXT DEFAULT NULL,
    blood_type VARCHAR(5) DEFAULT NULL,
    insurance_info TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE INDEX UNIQ_1ADAD7EBA76ED395 (user_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Create Medication table
CREATE TABLE medication (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(255) NOT NULL,
    unit_type VARCHAR(50) NOT NULL,
    unit_description VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    category VARCHAR(100) DEFAULT NULL,
    cost_price NUMERIC(10, 2) NOT NULL,
    selling_price NUMERIC(10, 2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    minimum_stock_level INT DEFAULT 10,
    expiry_date DATE DEFAULT NULL,
    supplier VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Create Queue table
CREATE TABLE queue (
    id INT AUTO_INCREMENT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    queue_number INT NOT NULL,
    queue_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'waiting',
    priority INT DEFAULT 0,
    estimated_time TIME DEFAULT NULL,
    arrival_time DATETIME DEFAULT NULL,
    called_time DATETIME DEFAULT NULL,
    completed_time DATETIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX IDX_7FFD7F636B899279 (patient_id),
    INDEX IDX_7FFD7F6387F4FB17 (doctor_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Create Appointment table
CREATE TABLE appointment (
    id INT AUTO_INCREMENT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATETIME NOT NULL,
    status VARCHAR(20) DEFAULT 'scheduled',
    appointment_type VARCHAR(50) DEFAULT 'consultation',
    reason TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    reminder_sent TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX IDX_FE38F8446B899279 (patient_id),
    INDEX IDX_FE38F84487F4FB17 (doctor_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Create Consultation table
CREATE TABLE consultation (
    id INT AUTO_INCREMENT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    consultation_date DATETIME NOT NULL,
    chief_complaint TEXT DEFAULT NULL,
    history_of_present_illness TEXT DEFAULT NULL,
    physical_examination TEXT DEFAULT NULL,
    assessment TEXT DEFAULT NULL,
    plan TEXT DEFAULT NULL,
    diagnosis TEXT DEFAULT NULL,
    vital_signs JSON DEFAULT NULL,
    follow_up_date DATE DEFAULT NULL,
    consultation_fee NUMERIC(10, 2) DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'completed',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX IDX_964685A66B899279 (patient_id),
    INDEX IDX_964685A687F4FB17 (doctor_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Create Prescribed Medication table
CREATE TABLE prescribed_medication (
    id INT AUTO_INCREMENT NOT NULL,
    consultation_id INT NOT NULL,
    medication_id INT NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    frequency VARCHAR(100) NOT NULL,
    duration VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    instructions TEXT DEFAULT NULL,
    unit_price NUMERIC(10, 2) NOT NULL,
    total_price NUMERIC(10, 2) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX IDX_51C16B7062FF6CDF (consultation_id),
    INDEX IDX_51C16B702C4DE6DA (medication_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Create Medical Certificate table
CREATE TABLE medical_certificate (
    id INT AUTO_INCREMENT NOT NULL,
    consultation_id INT NOT NULL,
    certificate_number VARCHAR(50) NOT NULL,
    issue_date DATE NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    diagnosis TEXT NOT NULL,
    recommendations TEXT DEFAULT NULL,
    certificate_type VARCHAR(50) DEFAULT 'sick_leave',
    is_printed TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE INDEX UNIQ_BE8AAD6BC8E0F29 (certificate_number),
    INDEX IDX_BE8AAD6B62FF6CDF (consultation_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Create Payment table
CREATE TABLE payment (
    id INT AUTO_INCREMENT NOT NULL,
    consultation_id INT NOT NULL,
    amount NUMERIC(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_date DATETIME NOT NULL,
    transaction_id VARCHAR(100) DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'completed',
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX IDX_6D28840D62FF6CDF (consultation_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Create Setting table
CREATE TABLE setting (
    id INT AUTO_INCREMENT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE INDEX UNIQ_9F74B898A3A1CCFE (setting_key),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Create Receipt Counter table
CREATE TABLE receipt_counter (
    id INT AUTO_INCREMENT NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    last_number INT DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE INDEX UNIQ_E2A23D78C9AB3644 (year, month),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Create doctrine_migration_versions table
CREATE TABLE doctrine_migration_versions (
    version VARCHAR(191) NOT NULL,
    executed_at DATETIME DEFAULT NULL,
    execution_time INT DEFAULT NULL,
    PRIMARY KEY(version)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- =====================================================
-- FOREIGN KEY CONSTRAINTS
-- =====================================================

ALTER TABLE doctor ADD CONSTRAINT FK_1FC0F36AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);
ALTER TABLE clinic_assistant ADD CONSTRAINT FK_7B2C0ECDA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);
ALTER TABLE patient ADD CONSTRAINT FK_1ADAD7EBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);
ALTER TABLE queue ADD CONSTRAINT FK_7FFD7F636B899279 FOREIGN KEY (patient_id) REFERENCES patient (id);
ALTER TABLE queue ADD CONSTRAINT FK_7FFD7F6387F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id);
ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8446B899279 FOREIGN KEY (patient_id) REFERENCES patient (id);
ALTER TABLE appointment ADD CONSTRAINT FK_FE38F84487F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id);
ALTER TABLE consultation ADD CONSTRAINT FK_964685A66B899279 FOREIGN KEY (patient_id) REFERENCES patient (id);
ALTER TABLE consultation ADD CONSTRAINT FK_964685A687F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id);
ALTER TABLE prescribed_medication ADD CONSTRAINT FK_51C16B7062FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id);
ALTER TABLE prescribed_medication ADD CONSTRAINT FK_51C16B702C4DE6DA FOREIGN KEY (medication_id) REFERENCES medication (id);
ALTER TABLE medical_certificate ADD CONSTRAINT FK_BE8AAD6B62FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id);
ALTER TABLE payment ADD CONSTRAINT FK_6D28840D62FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id);

-- =====================================================
-- ESSENTIAL TEST DATA
-- =====================================================

-- Insert test settings
INSERT INTO setting (setting_key, setting_value, description, created_at, updated_at) VALUES
('clinic_name', 'Test Clinic', 'Test clinic name', NOW(), NOW()),
('clinic_address', '123 Test Street', 'Test clinic address', NOW(), NOW()),
('clinic_phone', '555-0123', 'Test clinic phone', NOW(), NOW()),
('clinic_email', 'test@clinic.com', 'Test clinic email', NOW(), NOW()),
('timezone', 'Asia/Singapore', 'Test clinic timezone', NOW(), NOW());

-- Insert test users (password is '123Qwerty' for all test users)
INSERT INTO user (username, roles, password, email, first_name, last_name, phone, address, is_active, created_at, updated_at) VALUES
('admin', '["ROLE_ADMIN"]', '$2y$13$8K3Z5F9v2J7m3Q8p1N4xR.eZGxJ3kT5mF8vQ7A9nY4hZ2cE1mL6uC', 'admin@clinic.com', 'Admin', 'User', '555-0001', '123 Admin St', 1, NOW(), NOW()),
('doctor', '["ROLE_DOCTOR"]', '$2y$13$8K3Z5F9v2J7m3Q8p1N4xR.eZGxJ3kT5mF8vQ7A9nY4hZ2cE1mL6uC', 'doctor@clinic.com', 'Test', 'Doctor', '555-0002', '456 Doctor Ave', 1, NOW(), NOW()),
('patient', '["ROLE_PATIENT"]', '$2y$13$8K3Z5F9v2J7m3Q8p1N4xR.eZGxJ3kT5mF8vQ7A9nY4hZ2cE1mL6uC', 'patient@clinic.com', 'Test', 'Patient', '555-0003', '789 Patient Rd', 1, NOW(), NOW());

-- Insert test doctor
INSERT INTO doctor (user_id, specialization, license_number, consultation_fee, years_of_experience, qualifications, available_days, available_hours, is_available, created_at, updated_at) VALUES
(2, 'General Practice', 'TEST001', 50.00, 5, 'MBBS', 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:00-17:00', 1, NOW(), NOW());

-- Insert test patient
INSERT INTO patient (user_id, date_of_birth, gender, emergency_contact_name, emergency_contact_phone, medical_history, allergies, blood_type, created_at, updated_at) VALUES
(3, '1990-01-01', 'Male', 'Test Emergency', '555-9999', 'No significant medical history', 'None known', 'O+', NOW(), NOW());

-- Insert test medications
INSERT INTO medication (name, unit_type, unit_description, description, category, cost_price, selling_price, stock_quantity, created_at, updated_at) VALUES
('Test Paracetamol 500mg', 'tablet', 'Tablet', 'Test pain relief medication', 'Analgesic', 0.05, 0.15, 100, NOW(), NOW()),
('Test Ibuprofen 400mg', 'tablet', 'Tablet', 'Test anti-inflammatory medication', 'NSAID', 0.08, 0.25, 100, NOW(), NOW());

-- Initialize receipt counter
INSERT INTO receipt_counter (year, month, last_number, created_at, updated_at) VALUES
(YEAR(NOW()), MONTH(NOW()), 0, NOW(), NOW());

-- =====================================================
-- COMPLETE - TEST DATABASE READY
-- =====================================================
SELECT 'Test database setup completed successfully!' as message;
