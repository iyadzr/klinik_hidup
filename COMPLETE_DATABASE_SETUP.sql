-- =====================================================
-- COMPLETE CLINIC MANAGEMENT SYSTEM DATABASE SETUP
-- =====================================================
-- This file contains EVERYTHING: schema + data + fixes
-- Run this ONE file to get a complete working database

-- Use existing database
USE clinic_db;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop all tables for clean install
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
-- CREATE ALL TABLES WITH COMPLETE SCHEMA
-- =====================================================

-- User table
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
    profile_image VARCHAR(255) DEFAULT NULL,
    UNIQUE INDEX UNIQ_8D93D649F85E0677 (username),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Doctor table
CREATE TABLE doctor (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    specialization VARCHAR(255) NOT NULL,
    license_number VARCHAR(255) DEFAULT NULL,
    working_hours JSON NOT NULL,
    user_id INT DEFAULT NULL,
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
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Medication table (WITH PRICE FIELDS)
CREATE TABLE medication (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(255) NOT NULL,
    unit_type VARCHAR(50) NOT NULL,
    unit_description VARCHAR(100) DEFAULT NULL,
    description VARCHAR(500) DEFAULT NULL,
    category VARCHAR(100) DEFAULT NULL,
    cost_price DECIMAL(10, 2) DEFAULT NULL,
    selling_price DECIMAL(10, 2) DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Queue table
CREATE TABLE queue (
    id INT AUTO_INCREMENT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    queue_date_time DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    status VARCHAR(20) NOT NULL,
    queue_number VARCHAR(20) DEFAULT NULL,
    registration_number INT NOT NULL,
    INDEX IDX_7FFD7F636B899279 (patient_id),
    INDEX IDX_7FFD7F6387F4FB17 (doctor_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Appointment table
CREATE TABLE appointment (
    id INT AUTO_INCREMENT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date_time DATETIME NOT NULL,
    reason VARCHAR(1000) DEFAULT NULL,
    status VARCHAR(20) NOT NULL,
    notes VARCHAR(1000) DEFAULT NULL,
    INDEX IDX_FE38F8446B899279 (patient_id),
    INDEX IDX_FE38F84487F4FB17 (doctor_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Consultation table (WITH ALL FIELDS)
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
    total_amount NUMERIC(10, 2) NOT NULL,
    is_paid TINYINT(1) NOT NULL,
    paid_at DATETIME DEFAULT NULL,
    receipt_number VARCHAR(50) DEFAULT NULL,
    has_medical_certificate TINYINT(1) DEFAULT NULL,
    mc_start_date DATETIME DEFAULT NULL,
    mc_end_date DATETIME DEFAULT NULL,
    mc_number VARCHAR(255) DEFAULT NULL,
    mc_running_number VARCHAR(255) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    INDEX IDX_964685A66B899279 (patient_id),
    INDEX IDX_964685A687F4FB17 (doctor_id),
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
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Prescribed Medication table (WITH ACTUAL PRICE)
CREATE TABLE prescribed_medication (
    id INT AUTO_INCREMENT NOT NULL,
    consultation_id INT NOT NULL,
    medication_id INT NOT NULL,
    quantity INT NOT NULL,
    instructions VARCHAR(500) DEFAULT NULL,
    actual_price DECIMAL(10, 2) DEFAULT NULL,
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
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Settings table
CREATE TABLE setting (
    id INT AUTO_INCREMENT NOT NULL,
    setting_key VARCHAR(255) NOT NULL,
    setting_value LONGTEXT DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    UNIQUE INDEX UNIQ_SETTING_KEY (setting_key),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Receipt Counter table
CREATE TABLE receipt_counter (
    id INT AUTO_INCREMENT NOT NULL,
    current_number INT NOT NULL DEFAULT 1,
    year INT NOT NULL,
    month INT NOT NULL,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Migration versions table
CREATE TABLE doctrine_migration_versions (
    version VARCHAR(191) NOT NULL,
    executed_at DATETIME DEFAULT NULL,
    execution_time INT DEFAULT NULL,
    PRIMARY KEY(version)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- =====================================================
-- ADD FOREIGN KEY CONSTRAINTS
-- =====================================================

ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8446B899279 FOREIGN KEY (patient_id) REFERENCES patient (id);
ALTER TABLE appointment ADD CONSTRAINT FK_FE38F84487F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id);
ALTER TABLE consultation ADD CONSTRAINT FK_964685A66B899279 FOREIGN KEY (patient_id) REFERENCES patient (id);
ALTER TABLE consultation ADD CONSTRAINT FK_964685A687F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id);
ALTER TABLE medical_certificate ADD CONSTRAINT FK_B36515F86B899279 FOREIGN KEY (patient_id) REFERENCES patient (id);
ALTER TABLE medical_certificate ADD CONSTRAINT FK_B36515F887F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id);
ALTER TABLE patient ADD CONSTRAINT FK_1ADAD7EB27E92E18 FOREIGN KEY (registered_by_id) REFERENCES clinic_assistant (id);
ALTER TABLE payment ADD CONSTRAINT FK_6D28840D62FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id);
ALTER TABLE prescribed_medication ADD CONSTRAINT FK_51C16B7062FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id);
ALTER TABLE prescribed_medication ADD CONSTRAINT FK_51C16B702C4DE6DA FOREIGN KEY (medication_id) REFERENCES medication (id);
ALTER TABLE queue ADD CONSTRAINT FK_7FFD7F636B899279 FOREIGN KEY (patient_id) REFERENCES patient (id);
ALTER TABLE queue ADD CONSTRAINT FK_7FFD7F6387F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id);
ALTER TABLE doctor ADD CONSTRAINT FK_DOCTOR_USER FOREIGN KEY (user_id) REFERENCES user (id);

-- =====================================================
-- INSERT ALL ESSENTIAL DATA
-- =====================================================

-- Insert Users
INSERT INTO user (id, username, email, roles, password, name, allowed_pages, created_at, updated_at, is_active) VALUES
(1, 'superadmin', 'admin@clinic.com', '["ROLE_SUPER_ADMIN"]', '$2y$13$tarOU6Z5kowOY.blp0rkv.bR5GCxz9sn.uhaGe07aJ7H8XvYYfaWK', 'Super Admin', '[]', NOW(), NOW(), 1),
(2, 'dr.adhar', 'dr.adhar@clinic.com', '["ROLE_DOCTOR"]', '$2y$13$tarOU6Z5kowOY.blp0rkv.bR5GCxz9sn.uhaGe07aJ7H8XvYYfaWK', 'Dr. Md Adhar bin Tahir', '[]', NOW(), NOW(), 1),
(3, 'assistant1', 'assistant@clinic.com', '["ROLE_ASSISTANT"]', '$2y$13$tarOU6Z5kowOY.blp0rkv.bR5GCxz9sn.uhaGe07aJ7H8XvYYfaWK', 'Clinic Assistant', '[]', NOW(), NOW(), 1),
(4, 'dr.sarah', 'dr.sarah@clinic.com', '["ROLE_DOCTOR"]', '$2y$13$tarOU6Z5kowOY.blp0rkv.bR5GCxz9sn.uhaGe07aJ7H8XvYYfaWK', 'Dr. Sarah Johnson', '[]', NOW(), NOW(), 1);

-- Insert Doctors
INSERT INTO doctor (id, name, email, phone, specialization, license_number, working_hours, user_id) VALUES
(1, 'Dr. Md Adhar bin Tahir', 'dr.adhar@clinic.com', '+60123456789', 'General Practitioner', 'MMC123456', '{"monday": {"start": "09:00", "end": "17:00"}, "tuesday": {"start": "09:00", "end": "17:00"}, "wednesday": {"start": "09:00", "end": "17:00"}, "thursday": {"start": "09:00", "end": "17:00"}, "friday": {"start": "09:00", "end": "17:00"}, "saturday": {"start": "09:00", "end": "13:00"}, "sunday": {"closed": true}}', 2),
(2, 'Dr. Sarah Johnson', 'dr.sarah@clinic.com', '+60123456790', 'Pediatrician', 'MMC789012', '{"monday": {"start": "10:00", "end": "18:00"}, "tuesday": {"start": "10:00", "end": "18:00"}, "wednesday": {"start": "10:00", "end": "18:00"}, "thursday": {"start": "10:00", "end": "18:00"}, "friday": {"start": "10:00", "end": "18:00"}, "saturday": {"closed": true}, "sunday": {"closed": true}}', 4);

-- Insert Clinic Assistants
INSERT INTO clinic_assistant (id, name, email, phone, username, password) VALUES
(1, 'Nurse Mary', 'mary@clinic.com', '+60123456791', 'mary', '$2y$13$tarOU6Z5kowOY.blp0rkv.bR5GCxz9sn.uhaGe07aJ7H8XvYYfaWK'),
(2, 'Assistant John', 'john@clinic.com', '+60123456792', 'john', '$2y$13$tarOU6Z5kowOY.blp0rkv.bR5GCxz9sn.uhaGe07aJ7H8XvYYfaWK');

-- Insert Sample Patients
INSERT INTO patient (id, registered_by_id, name, nric, email, phone, date_of_birth, medical_history, company, remarks, gender, address) VALUES
(1, 1, 'Siti Maisarah binti Zaharin', '950123-05-1234', 'maisarah@email.com', '+60123456793', '1995-01-23', 'No known allergies', 'Tech Company Sdn Bhd', 'Fever and cough', 'F', 'Kuala Lumpur, Malaysia'),
(2, 1, 'Ahmad bin Abdullah', '880615-14-5678', 'ahmad@email.com', '+60123456794', '1988-06-15', 'Diabetes Type 2', 'Government Office', 'Regular checkup', 'M', 'Selangor, Malaysia'),
(3, 1, 'Fatimah binti Hassan', '920308-06-9012', 'fatimah@email.com', '+60123456795', '1992-03-08', 'Hypertension', 'Banking Sector', 'Headache', 'F', 'Petaling Jaya, Malaysia');

-- Insert Basic Settings
INSERT INTO setting (setting_key, setting_value, created_at, updated_at) VALUES
('clinic_name', 'Klinik HiDUP Sihat', NOW(), NOW()),
('clinic_address', 'Kuala Lumpur, Malaysia', NOW(), NOW()),
('clinic_phone', '+60123456789', NOW(), NOW()),
('consultation_fee', '50.00', NOW(), NOW());

-- Insert Receipt Counter
INSERT INTO receipt_counter (id, current_number, year, month) VALUES
(1, 1, YEAR(NOW()), MONTH(NOW()));

-- =====================================================
-- INSERT MEDICATIONS (BATCH 1 - 50 items)
-- =====================================================
INSERT INTO medication (name, unit_type, unit_description, description, category, cost_price, selling_price, created_at, updated_at) VALUES
('Paracetamol 500mg', 'tablet', 'Tablet', 'Pain relief and fever reducer', 'Analgesic', 0.10, 0.25, NOW(), NOW()),
('Ibuprofen 400mg', 'tablet', 'Tablet', 'Anti-inflammatory pain reliever', 'NSAID', 0.15, 0.35, NOW(), NOW()),
('Amoxicillin 500mg', 'capsule', 'Capsule', 'Antibiotic for bacterial infections', 'Antibiotic', 0.25, 0.60, NOW(), NOW()),
('Cetirizine 10mg', 'tablet', 'Tablet', 'Antihistamine for allergies', 'Antihistamine', 0.20, 0.45, NOW(), NOW()),
('Omeprazole 20mg', 'capsule', 'Capsule', 'Proton pump inhibitor for acid reflux', 'PPI', 0.30, 0.75, NOW(), NOW()),
('Metformin 500mg', 'tablet', 'Tablet', 'Diabetes medication', 'Antidiabetic', 0.12, 0.30, NOW(), NOW()),
('Amlodipine 5mg', 'tablet', 'Tablet', 'Blood pressure medication', 'Antihypertensive', 0.18, 0.40, NOW(), NOW()),
('Simvastatin 20mg', 'tablet', 'Tablet', 'Cholesterol lowering medication', 'Statin', 0.22, 0.50, NOW(), NOW()),
('Aspirin 100mg', 'tablet', 'Tablet', 'Blood thinner and pain relief', 'Antiplatelet', 0.08, 0.20, NOW(), NOW()),
('Loratadine 10mg', 'tablet', 'Tablet', 'Non-drowsy antihistamine', 'Antihistamine', 0.16, 0.38, NOW(), NOW()),
('Dextromethorphan 15mg', 'syrup', '5ml', 'Cough suppressant', 'Antitussive', 0.50, 1.20, NOW(), NOW()),
('Salbutamol 100mcg', 'inhaler', 'Puff', 'Bronchodilator for asthma', 'Bronchodilator', 2.00, 4.50, NOW(), NOW()),
('Prednisolone 5mg', 'tablet', 'Tablet', 'Corticosteroid anti-inflammatory', 'Corticosteroid', 0.25, 0.55, NOW(), NOW()),
('Ciprofloxacin 500mg', 'tablet', 'Tablet', 'Broad spectrum antibiotic', 'Antibiotic', 0.35, 0.80, NOW(), NOW()),
('Diclofenac 50mg', 'tablet', 'Tablet', 'Anti-inflammatory pain reliever', 'NSAID', 0.20, 0.45, NOW(), NOW()),
('Chlorpheniramine 4mg', 'tablet', 'Tablet', 'Antihistamine for allergies', 'Antihistamine', 0.12, 0.28, NOW(), NOW()),
('Ranitidine 150mg', 'tablet', 'Tablet', 'H2 receptor antagonist', 'Antacid', 0.18, 0.42, NOW(), NOW()),
('Furosemide 40mg', 'tablet', 'Tablet', 'Diuretic for fluid retention', 'Diuretic', 0.15, 0.35, NOW(), NOW()),
('Atenolol 50mg', 'tablet', 'Tablet', 'Beta blocker for hypertension', 'Beta Blocker', 0.20, 0.48, NOW(), NOW()),
('Captopril 25mg', 'tablet', 'Tablet', 'ACE inhibitor for hypertension', 'ACE Inhibitor', 0.22, 0.52, NOW(), NOW()),
('Glibenclamide 5mg', 'tablet', 'Tablet', 'Diabetes medication', 'Antidiabetic', 0.14, 0.32, NOW(), NOW()),
('Nifedipine 10mg', 'tablet', 'Tablet', 'Calcium channel blocker', 'Antihypertensive', 0.25, 0.58, NOW(), NOW()),
('Warfarin 5mg', 'tablet', 'Tablet', 'Anticoagulant blood thinner', 'Anticoagulant', 0.30, 0.70, NOW(), NOW()),
('Digoxin 0.25mg', 'tablet', 'Tablet', 'Heart medication', 'Cardiac Glycoside', 0.35, 0.80, NOW(), NOW()),
('Phenytoin 100mg', 'capsule', 'Capsule', 'Anti-seizure medication', 'Anticonvulsant', 0.28, 0.65, NOW(), NOW()),
('Carbamazepine 200mg', 'tablet', 'Tablet', 'Anti-seizure and mood stabilizer', 'Anticonvulsant', 0.32, 0.75, NOW(), NOW()),
('Levothyroxine 50mcg', 'tablet', 'Tablet', 'Thyroid hormone replacement', 'Thyroid', 0.25, 0.60, NOW(), NOW()),
('Insulin Glargine', 'injection', 'Unit', 'Long-acting insulin', 'Insulin', 15.00, 35.00, NOW(), NOW()),
('Insulin Regular', 'injection', 'Unit', 'Short-acting insulin', 'Insulin', 12.00, 28.00, NOW(), NOW()),
('Morphine 10mg', 'tablet', 'Tablet', 'Strong pain reliever', 'Opioid', 1.50, 3.50, NOW(), NOW()),
('Codeine 30mg', 'tablet', 'Tablet', 'Mild opioid pain reliever', 'Opioid', 0.80, 1.80, NOW(), NOW()),
('Tramadol 50mg', 'capsule', 'Capsule', 'Moderate pain reliever', 'Analgesic', 0.45, 1.05, NOW(), NOW()),
('Fluoxetine 20mg', 'capsule', 'Capsule', 'Antidepressant SSRI', 'Antidepressant', 0.60, 1.40, NOW(), NOW()),
('Sertraline 50mg', 'tablet', 'Tablet', 'Antidepressant SSRI', 'Antidepressant', 0.65, 1.50, NOW(), NOW()),
('Diazepam 5mg', 'tablet', 'Tablet', 'Anxiolytic benzodiazepine', 'Anxiolytic', 0.40, 0.95, NOW(), NOW()),
('Lorazepam 1mg', 'tablet', 'Tablet', 'Short-acting anxiolytic', 'Anxiolytic', 0.50, 1.15, NOW(), NOW()),
('Zolpidem 10mg', 'tablet', 'Tablet', 'Sleep aid medication', 'Hypnotic', 0.70, 1.60, NOW(), NOW()),
('Melatonin 3mg', 'tablet', 'Tablet', 'Natural sleep aid', 'Sleep Aid', 0.35, 0.80, NOW(), NOW()),
('Vitamin D3 1000IU', 'tablet', 'Tablet', 'Vitamin D supplement', 'Vitamin', 0.15, 0.35, NOW(), NOW()),
('Vitamin B12 1000mcg', 'tablet', 'Tablet', 'Vitamin B12 supplement', 'Vitamin', 0.20, 0.45, NOW(), NOW()),
('Folic Acid 5mg', 'tablet', 'Tablet', 'Folate supplement', 'Vitamin', 0.12, 0.28, NOW(), NOW()),
('Iron Sulfate 325mg', 'tablet', 'Tablet', 'Iron supplement for anemia', 'Mineral', 0.18, 0.40, NOW(), NOW()),
('Calcium Carbonate 500mg', 'tablet', 'Tablet', 'Calcium supplement', 'Mineral', 0.14, 0.32, NOW(), NOW()),
('Magnesium Oxide 400mg', 'tablet', 'Tablet', 'Magnesium supplement', 'Mineral', 0.16, 0.38, NOW(), NOW()),
('Zinc Sulfate 220mg', 'tablet', 'Tablet', 'Zinc supplement', 'Mineral', 0.22, 0.50, NOW(), NOW()),
('Multivitamin', 'tablet', 'Tablet', 'Daily multivitamin supplement', 'Vitamin', 0.25, 0.60, NOW(), NOW()),
('Probiotics', 'capsule', 'Capsule', 'Beneficial bacteria supplement', 'Supplement', 0.80, 1.80, NOW(), NOW()),
('Omega-3 Fish Oil', 'capsule', 'Capsule', 'Essential fatty acid supplement', 'Supplement', 0.60, 1.40, NOW(), NOW()),
('Glucosamine 1500mg', 'tablet', 'Tablet', 'Joint health supplement', 'Supplement', 0.70, 1.60, NOW(), NOW()),
('Coenzyme Q10 100mg', 'capsule', 'Capsule', 'Antioxidant supplement', 'Supplement', 1.20, 2.80, NOW(), NOW());

-- BATCH 2 - Another 50 medications
INSERT INTO medication (name, unit_type, unit_description, description, category, cost_price, selling_price, created_at, updated_at) VALUES
('Azithromycin 250mg', 'tablet', 'Tablet', 'Macrolide antibiotic', 'Antibiotic', 0.45, 1.05, NOW(), NOW()),
('Doxycycline 100mg', 'capsule', 'Capsule', 'Tetracycline antibiotic', 'Antibiotic', 0.35, 0.80, NOW(), NOW()),
('Clarithromycin 500mg', 'tablet', 'Tablet', 'Macrolide antibiotic', 'Antibiotic', 0.60, 1.40, NOW(), NOW()),
('Erythromycin 250mg', 'tablet', 'Tablet', 'Macrolide antibiotic', 'Antibiotic', 0.30, 0.70, NOW(), NOW()),
('Penicillin V 500mg', 'tablet', 'Tablet', 'Beta-lactam antibiotic', 'Antibiotic', 0.20, 0.45, NOW(), NOW()),
('Cephalexin 500mg', 'capsule', 'Capsule', 'Cephalosporin antibiotic', 'Antibiotic', 0.40, 0.90, NOW(), NOW()),
('Clindamycin 300mg', 'capsule', 'Capsule', 'Lincosamide antibiotic', 'Antibiotic', 0.50, 1.15, NOW(), NOW()),
('Metronidazole 400mg', 'tablet', 'Tablet', 'Antiprotozoal antibiotic', 'Antibiotic', 0.25, 0.55, NOW(), NOW()),
('Fluconazole 150mg', 'capsule', 'Capsule', 'Antifungal medication', 'Antifungal', 0.80, 1.80, NOW(), NOW()),
('Ketoconazole 200mg', 'tablet', 'Tablet', 'Antifungal medication', 'Antifungal', 0.60, 1.35, NOW(), NOW()),
('Acyclovir 400mg', 'tablet', 'Tablet', 'Antiviral medication', 'Antiviral', 0.70, 1.60, NOW(), NOW()),
('Oseltamivir 75mg', 'capsule', 'Capsule', 'Antiviral for influenza', 'Antiviral', 2.50, 5.50, NOW(), NOW()),
('Hydroxyzine 25mg', 'tablet', 'Tablet', 'Antihistamine and anxiolytic', 'Antihistamine', 0.18, 0.40, NOW(), NOW()),
('Promethazine 25mg', 'tablet', 'Tablet', 'Antihistamine and antiemetic', 'Antihistamine', 0.22, 0.50, NOW(), NOW()),
('Diphenhydramine 25mg', 'capsule', 'Capsule', 'Antihistamine and sleep aid', 'Antihistamine', 0.15, 0.35, NOW(), NOW()),
('Meclizine 25mg', 'tablet', 'Tablet', 'Motion sickness medication', 'Antihistamine', 0.25, 0.55, NOW(), NOW()),
('Ondansetron 4mg', 'tablet', 'Tablet', 'Anti-nausea medication', 'Antiemetic', 1.20, 2.70, NOW(), NOW()),
('Metoclopramide 10mg', 'tablet', 'Tablet', 'Prokinetic antiemetic', 'Antiemetic', 0.18, 0.40, NOW(), NOW()),
('Domperidone 10mg', 'tablet', 'Tablet', 'Prokinetic antiemetic', 'Antiemetic', 0.20, 0.45, NOW(), NOW()),
('Loperamide 2mg', 'capsule', 'Capsule', 'Anti-diarrheal medication', 'Antidiarrheal', 0.15, 0.35, NOW(), NOW()),
('Bismuth Subsalicylate', 'tablet', 'Tablet', 'Anti-diarrheal and stomach protectant', 'Antidiarrheal', 0.12, 0.28, NOW(), NOW()),
('Simethicone 40mg', 'tablet', 'Tablet', 'Anti-gas medication', 'Antiflatulent', 0.10, 0.25, NOW(), NOW()),
('Lactulose Syrup', 'syrup', '15ml', 'Laxative for constipation', 'Laxative', 0.80, 1.80, NOW(), NOW()),
('Senna 8.6mg', 'tablet', 'Tablet', 'Stimulant laxative', 'Laxative', 0.08, 0.20, NOW(), NOW()),
('Docusate Sodium 100mg', 'capsule', 'Capsule', 'Stool softener', 'Laxative', 0.12, 0.28, NOW(), NOW()),
('Polyethylene Glycol', 'powder', 'Sachet', 'Osmotic laxative', 'Laxative', 0.50, 1.15, NOW(), NOW()),
('Hydrocortisone 1% Cream', 'cream', 'Gram', 'Topical corticosteroid', 'Topical', 0.15, 0.35, NOW(), NOW()),
('Betamethasone 0.1% Cream', 'cream', 'Gram', 'Potent topical corticosteroid', 'Topical', 0.25, 0.55, NOW(), NOW()),
('Clotrimazole 1% Cream', 'cream', 'Gram', 'Antifungal cream', 'Topical', 0.20, 0.45, NOW(), NOW()),
('Mupirocin 2% Ointment', 'ointment', 'Gram', 'Topical antibiotic', 'Topical', 0.60, 1.35, NOW(), NOW()),
('Calamine Lotion', 'lotion', 'ml', 'Soothing skin lotion', 'Topical', 0.05, 0.12, NOW(), NOW()),
('Zinc Oxide Cream', 'cream', 'Gram', 'Protective barrier cream', 'Topical', 0.08, 0.18, NOW(), NOW()),
('Aloe Vera Gel', 'gel', 'Gram', 'Soothing skin gel', 'Topical', 0.10, 0.25, NOW(), NOW()),
('Lidocaine 2% Gel', 'gel', 'Gram', 'Topical anesthetic', 'Topical', 0.30, 0.70, NOW(), NOW()),
('Capsaicin 0.025% Cream', 'cream', 'Gram', 'Topical pain reliever', 'Topical', 0.40, 0.90, NOW(), NOW()),
('Menthol Rub', 'ointment', 'Gram', 'Topical pain reliever', 'Topical', 0.12, 0.28, NOW(), NOW()),
('Artificial Tears', 'drops', 'ml', 'Eye lubricant drops', 'Ophthalmology', 0.80, 1.80, NOW(), NOW()),
('Chloramphenicol Eye Drops', 'drops', 'ml', 'Antibiotic eye drops', 'Ophthalmology', 1.20, 2.70, NOW(), NOW()),
('Prednisolone Eye Drops', 'drops', 'ml', 'Corticosteroid eye drops', 'Ophthalmology', 1.50, 3.40, NOW(), NOW()),
('Timolol Eye Drops', 'drops', 'ml', 'Glaucoma medication', 'Ophthalmology', 2.00, 4.50, NOW(), NOW()),
('Latanoprost Eye Drops', 'drops', 'ml', 'Glaucoma medication', 'Ophthalmology', 8.00, 18.00, NOW(), NOW()),
('Oxymetazoline Nasal Spray', 'spray', 'ml', 'Nasal decongestant', 'Nasal', 0.60, 1.35, NOW(), NOW()),
('Saline Nasal Spray', 'spray', 'ml', 'Nasal moisturizer', 'Nasal', 0.40, 0.90, NOW(), NOW()),
('Fluticasone Nasal Spray', 'spray', 'ml', 'Corticosteroid nasal spray', 'Nasal', 2.50, 5.50, NOW(), NOW()),
('Nystatin Oral Suspension', 'suspension', 'ml', 'Antifungal oral medication', 'Antifungal', 1.20, 2.70, NOW(), NOW()),
('Benzocaine Oral Gel', 'gel', 'Gram', 'Oral anesthetic gel', 'Oral', 0.80, 1.80, NOW(), NOW()),
('Hydrogen Peroxide 3%', 'solution', 'ml', 'Antiseptic solution', 'Antiseptic', 0.05, 0.12, NOW(), NOW()),
('Povidone Iodine 10%', 'solution', 'ml', 'Antiseptic solution', 'Antiseptic', 0.08, 0.18, NOW(), NOW()),
('Alcohol 70%', 'solution', 'ml', 'Antiseptic alcohol', 'Antiseptic', 0.03, 0.08, NOW(), NOW()),
('Chlorhexidine 0.2%', 'solution', 'ml', 'Antiseptic mouthwash', 'Antiseptic', 0.15, 0.35, NOW(), NOW());

-- BATCH 3 - Another 50 medications
INSERT INTO medication (name, unit_type, unit_description, description, category, cost_price, selling_price, created_at, updated_at) VALUES
('Losartan 50mg', 'tablet', 'Tablet', 'ARB for hypertension', 'Antihypertensive', 0.30, 0.70, NOW(), NOW()),
('Valsartan 80mg', 'tablet', 'Tablet', 'ARB for hypertension', 'Antihypertensive', 0.35, 0.80, NOW(), NOW()),
('Lisinopril 10mg', 'tablet', 'Tablet', 'ACE inhibitor', 'ACE Inhibitor', 0.25, 0.58, NOW(), NOW()),
('Enalapril 5mg', 'tablet', 'Tablet', 'ACE inhibitor', 'ACE Inhibitor', 0.20, 0.45, NOW(), NOW()),
('Metoprolol 50mg', 'tablet', 'Tablet', 'Beta blocker', 'Beta Blocker', 0.22, 0.50, NOW(), NOW()),
('Propranolol 40mg', 'tablet', 'Tablet', 'Beta blocker', 'Beta Blocker', 0.18, 0.40, NOW(), NOW()),
('Carvedilol 6.25mg', 'tablet', 'Tablet', 'Alpha-beta blocker', 'Beta Blocker', 0.40, 0.90, NOW(), NOW()),
('Diltiazem 60mg', 'tablet', 'Tablet', 'Calcium channel blocker', 'Antihypertensive', 0.28, 0.65, NOW(), NOW()),
('Verapamil 80mg', 'tablet', 'Tablet', 'Calcium channel blocker', 'Antihypertensive', 0.25, 0.58, NOW(), NOW()),
('Hydrochlorothiazide 25mg', 'tablet', 'Tablet', 'Thiazide diuretic', 'Diuretic', 0.12, 0.28, NOW(), NOW()),
('Spironolactone 25mg', 'tablet', 'Tablet', 'Potassium-sparing diuretic', 'Diuretic', 0.20, 0.45, NOW(), NOW()),
('Indapamide 2.5mg', 'tablet', 'Tablet', 'Thiazide-like diuretic', 'Diuretic', 0.18, 0.40, NOW(), NOW()),
('Atorvastatin 20mg', 'tablet', 'Tablet', 'HMG-CoA reductase inhibitor', 'Statin', 0.25, 0.58, NOW(), NOW()),
('Rosuvastatin 10mg', 'tablet', 'Tablet', 'HMG-CoA reductase inhibitor', 'Statin', 0.35, 0.80, NOW(), NOW()),
('Pravastatin 40mg', 'tablet', 'Tablet', 'HMG-CoA reductase inhibitor', 'Statin', 0.30, 0.70, NOW(), NOW()),
('Lovastatin 20mg', 'tablet', 'Tablet', 'HMG-CoA reductase inhibitor', 'Statin', 0.22, 0.50, NOW(), NOW()),
('Fenofibrate 160mg', 'tablet', 'Tablet', 'Fibrate for cholesterol', 'Fibrate', 0.40, 0.90, NOW(), NOW()),
('Gemfibrozil 600mg', 'tablet', 'Tablet', 'Fibrate for cholesterol', 'Fibrate', 0.35, 0.80, NOW(), NOW()),
('Clopidogrel 75mg', 'tablet', 'Tablet', 'Antiplatelet agent', 'Antiplatelet', 0.80, 1.80, NOW(), NOW()),
('Dipyridamole 25mg', 'tablet', 'Tablet', 'Antiplatelet agent', 'Antiplatelet', 0.15, 0.35, NOW(), NOW()),
('Heparin 5000 IU', 'injection', 'ml', 'Anticoagulant injection', 'Anticoagulant', 2.00, 4.50, NOW(), NOW()),
('Enoxaparin 40mg', 'injection', 'ml', 'Low molecular weight heparin', 'Anticoagulant', 8.00, 18.00, NOW(), NOW()),
('Gliclazide 80mg', 'tablet', 'Tablet', 'Sulfonylurea for diabetes', 'Antidiabetic', 0.18, 0.40, NOW(), NOW()),
('Glimepiride 2mg', 'tablet', 'Tablet', 'Sulfonylurea for diabetes', 'Antidiabetic', 0.20, 0.45, NOW(), NOW()),
('Pioglitazone 15mg', 'tablet', 'Tablet', 'Thiazolidinedione for diabetes', 'Antidiabetic', 0.60, 1.35, NOW(), NOW()),
('Sitagliptin 100mg', 'tablet', 'Tablet', 'DPP-4 inhibitor for diabetes', 'Antidiabetic', 2.50, 5.50, NOW(), NOW()),
('Acarbose 50mg', 'tablet', 'Tablet', 'Alpha-glucosidase inhibitor', 'Antidiabetic', 0.40, 0.90, NOW(), NOW()),
('Repaglinide 1mg', 'tablet', 'Tablet', 'Meglitinide for diabetes', 'Antidiabetic', 0.50, 1.15, NOW(), NOW()),
('Allopurinol 100mg', 'tablet', 'Tablet', 'Xanthine oxidase inhibitor', 'Antigout', 0.15, 0.35, NOW(), NOW()),
('Colchicine 0.5mg', 'tablet', 'Tablet', 'Anti-inflammatory for gout', 'Antigout', 0.80, 1.80, NOW(), NOW()),
('Probenecid 500mg', 'tablet', 'Tablet', 'Uricosuric agent', 'Antigout', 0.60, 1.35, NOW(), NOW()),
('Febuxostat 80mg', 'tablet', 'Tablet', 'Xanthine oxidase inhibitor', 'Antigout', 2.00, 4.50, NOW(), NOW()),
('Alendronate 70mg', 'tablet', 'Tablet', 'Bisphosphonate for osteoporosis', 'Bone Health', 1.20, 2.70, NOW(), NOW()),
('Risedronate 35mg', 'tablet', 'Tablet', 'Bisphosphonate for osteoporosis', 'Bone Health', 1.50, 3.40, NOW(), NOW()),
('Calcitriol 0.25mcg', 'capsule', 'Capsule', 'Active vitamin D', 'Bone Health', 0.80, 1.80, NOW(), NOW()),
('Raloxifene 60mg', 'tablet', 'Tablet', 'SERM for osteoporosis', 'Bone Health', 2.50, 5.50, NOW(), NOW()),
('Teriparatide 20mcg', 'injection', 'ml', 'Parathyroid hormone analog', 'Bone Health', 50.00, 110.00, NOW(), NOW()),
('Baclofen 10mg', 'tablet', 'Tablet', 'Muscle relaxant', 'Muscle Relaxant', 0.25, 0.58, NOW(), NOW()),
('Cyclobenzaprine 10mg', 'tablet', 'Tablet', 'Muscle relaxant', 'Muscle Relaxant', 0.30, 0.70, NOW(), NOW()),
('Methocarbamol 750mg', 'tablet', 'Tablet', 'Muscle relaxant', 'Muscle Relaxant', 0.35, 0.80, NOW(), NOW()),
('Tizanidine 4mg', 'tablet', 'Tablet', 'Muscle relaxant', 'Muscle Relaxant', 0.40, 0.90, NOW(), NOW()),
('Orphenadrine 100mg', 'tablet', 'Tablet', 'Muscle relaxant', 'Muscle Relaxant', 0.45, 1.05, NOW(), NOW()),
('Gabapentin 300mg', 'capsule', 'Capsule', 'Anticonvulsant for neuropathy', 'Anticonvulsant', 0.50, 1.15, NOW(), NOW()),
('Pregabalin 75mg', 'capsule', 'Capsule', 'Anticonvulsant for neuropathy', 'Anticonvulsant', 1.20, 2.70, NOW(), NOW()),
('Valproic Acid 500mg', 'tablet', 'Tablet', 'Anticonvulsant', 'Anticonvulsant', 0.40, 0.90, NOW(), NOW()),
('Lamotrigine 100mg', 'tablet', 'Tablet', 'Anticonvulsant', 'Anticonvulsant', 0.80, 1.80, NOW(), NOW()),
('Levetiracetam 500mg', 'tablet', 'Tablet', 'Anticonvulsant', 'Anticonvulsant', 1.50, 3.40, NOW(), NOW()),
('Topiramate 50mg', 'tablet', 'Tablet', 'Anticonvulsant', 'Anticonvulsant', 0.60, 1.35, NOW(), NOW()),
('Oxcarbazepine 300mg', 'tablet', 'Tablet', 'Anticonvulsant', 'Anticonvulsant', 0.70, 1.60, NOW(), NOW()),
('Clonazepam 0.5mg', 'tablet', 'Tablet', 'Benzodiazepine anticonvulsant', 'Anticonvulsant', 0.25, 0.58, NOW(), NOW());

-- Insert Migration Versions
INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES
('DoctrineMigrations\\Version20250611092531', NOW(), 1),
('DoctrineMigrations\\Version20250621000000', NOW(), 1),
('DoctrineMigrations\\Version20250122000000_AddPriceFieldsToMedication', NOW(), 1);

-- =====================================================
-- DONE! Database is ready with schema + data
-- ===================================================== 