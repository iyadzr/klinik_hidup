-- Insert Essential Data for Clinic Management System
-- Run this after the database schema is created

USE clinic_db;

-- Insert Users (for authentication)
INSERT INTO user (id, username, email, roles, password, name, allowed_pages, created_at, updated_at, is_active) VALUES
(1, 'superadmin', 'admin@clinic.com', '["ROLE_SUPER_ADMIN"]', '$2y$13$password_hash_here', 'Super Admin', '[]', NOW(), NOW(), 1),
(2, 'dr.adhar', 'dr.adhar@clinic.com', '["ROLE_DOCTOR"]', '$2y$13$password_hash_here', 'Dr. Md Adhar bin Tahir', '[]', NOW(), NOW(), 1),
(3, 'assistant1', 'assistant@clinic.com', '["ROLE_ASSISTANT"]', '$2y$13$password_hash_here', 'Clinic Assistant', '[]', NOW(), NOW(), 1);

-- Insert Doctors
INSERT INTO doctor (id, user_id, name, email, phone, specialization, license_number, working_hours) VALUES
(1, 2, 'Dr. Md Adhar bin Tahir', 'dr.adhar@clinic.com', '+60123456789', 'General Practitioner', 'MMC123456', '{"monday": {"start": "09:00", "end": "17:00"}, "tuesday": {"start": "09:00", "end": "17:00"}, "wednesday": {"start": "09:00", "end": "17:00"}, "thursday": {"start": "09:00", "end": "17:00"}, "friday": {"start": "09:00", "end": "17:00"}, "saturday": {"start": "09:00", "end": "13:00"}, "sunday": {"closed": true}}'),
(2, NULL, 'Dr. Sarah Johnson', 'dr.sarah@clinic.com', '+60123456790', 'Pediatrician', 'MMC789012', '{"monday": {"start": "10:00", "end": "18:00"}, "tuesday": {"start": "10:00", "end": "18:00"}, "wednesday": {"start": "10:00", "end": "18:00"}, "thursday": {"start": "10:00", "end": "18:00"}, "friday": {"start": "10:00", "end": "18:00"}, "saturday": {"closed": true}, "sunday": {"closed": true}}');

-- Insert Clinic Assistants
INSERT INTO clinic_assistant (id, name, email, phone, username, password) VALUES
(1, 'Nurse Mary', 'mary@clinic.com', '+60123456791', 'mary', '$2y$13$password_hash_here'),
(2, 'Assistant John', 'john@clinic.com', '+60123456792', 'john', '$2y$13$password_hash_here');

-- Insert Sample Patients
INSERT INTO patient (id, registered_by_id, name, nric, email, phone, date_of_birth, medical_history, company, pre_informed_illness, gender, address) VALUES
(1, 1, 'Siti Maisarah binti Zaharin', '950123-05-1234', 'maisarah@email.com', '+60123456793', '1995-01-23', 'No known allergies', 'Tech Company Sdn Bhd', '', 'F', 'Kuala Lumpur, Malaysia'),
(2, 1, 'Ahmad bin Abdullah', '880615-14-5678', 'ahmad@email.com', '+60123456794', '1988-06-15', 'Diabetes Type 2', 'Government Office', '', 'M', 'Selangor, Malaysia'),
(3, 1, 'Fatimah binti Hassan', '920308-06-9012', 'fatimah@email.com', '+60123456795', '1992-03-08', 'Hypertension', 'Banking Sector', '', 'F', 'Petaling Jaya, Malaysia');

-- Insert Basic Settings
INSERT INTO setting (setting_key, setting_value, created_at, updated_at) VALUES
('clinic_name', 'Klinik HiDUP Sihat', NOW(), NOW()),
('clinic_address', 'Kuala Lumpur, Malaysia', NOW(), NOW()),
('clinic_phone', '+60123456789', NOW(), NOW()),
('consultation_fee', '50.00', NOW(), NOW());

-- Insert Receipt Counter
INSERT INTO receipt_counter (id, current_number, year, month) VALUES
(1, 1, YEAR(NOW()), MONTH(NOW()));

-- Insert Migration Version (to mark schema as up to date)
INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES
('DoctrineMigrations\\Version20250611092531', NOW(), 1),
('DoctrineMigrations\\Version20250621000000', NOW(), 1); 