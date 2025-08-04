-- =============================================================================
-- USER CREATION DEPLOYMENT SCRIPT
-- =============================================================================
-- This script safely creates 3 types of users: doctor, clinic_assistant, superadmin
-- with proper error handling and duplicate prevention
-- =============================================================================

USE clinic_db;

-- Set proper SQL mode and timezone
SET sql_mode = 'TRADITIONAL';
SET time_zone = '+08:00';

-- =============================================================================
-- 1. CREATE SUPERADMIN USER
-- =============================================================================

-- Check if superadmin user already exists
SET @superadmin_exists = (
    SELECT COUNT(*) 
    FROM user 
    WHERE username = 'superadmin' OR email = 'superadmin@clinic.com'
);

-- Create or update superadmin user
SET @create_superadmin = IF(@superadmin_exists = 0,
    CONCAT('INSERT INTO user (username, email, name, password, roles, created_at, updated_at, is_active) VALUES ',
           '(''superadmin'', ''superadmin@clinic.com'', ''Super Administrator'', ',
           '''$2y$13$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy'', ', -- Password: admin123
           '''[\"ROLE_SUPER_ADMIN\"]'', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1)'),
    CONCAT('UPDATE user SET password = ''$2y$13$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy'', ', -- Password: admin123
           'roles = ''[\"ROLE_SUPER_ADMIN\"]'', updated_at = CURRENT_TIMESTAMP, is_active = 1 ',
           'WHERE username = ''superadmin'' OR email = ''superadmin@clinic.com''')
);

PREPARE stmt FROM @create_superadmin;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 2. CREATE DOCTOR USER
-- =============================================================================

-- Check if doctor user already exists
SET @doctor_exists = (
    SELECT COUNT(*) 
    FROM user 
    WHERE username = 'doctor' OR email = 'doctor@clinic.com'
);

-- Create or update doctor user
SET @create_doctor = IF(@doctor_exists = 0,
    CONCAT('INSERT INTO user (username, email, name, password, roles, created_at, updated_at, is_active) VALUES ',
           '(''doctor'', ''doctor@clinic.com'', ''Dr. John Smith'', ',
           '''$2y$13$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy'', ', -- Password: admin123
           '''[\"ROLE_DOCTOR\"]'', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1)'),
    CONCAT('UPDATE user SET password = ''$2y$13$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy'', ', -- Password: admin123
           'roles = ''[\"ROLE_DOCTOR\"]'', updated_at = CURRENT_TIMESTAMP, is_active = 1 ',
           'WHERE username = ''doctor'' OR email = ''doctor@clinic.com''')
);

PREPARE stmt FROM @create_doctor;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create doctor profile if user was created and profile doesn't exist
SET @doctor_user_id = (SELECT id FROM user WHERE username = 'doctor' LIMIT 1);
SET @doctor_profile_exists = (
    SELECT COUNT(*) 
    FROM doctor 
    WHERE user_id = @doctor_user_id
);

SET @create_doctor_profile = IF(@doctor_profile_exists = 0 AND @doctor_user_id IS NOT NULL,
    CONCAT('INSERT INTO doctor (name, email, phone, specialization, license_number, working_hours, user_id) VALUES ',
           '(''Dr. John Smith'', ''doctor@clinic.com'', ''+60123456789'', ''General Practice'', ''MD123456'', ',
           '''{"monday": {"start": "09:00", "end": "17:00"}, "tuesday": {"start": "09:00", "end": "17:00"}, "wednesday": {"start": "09:00", "end": "17:00"}, "thursday": {"start": "09:00", "end": "17:00"}, "friday": {"start": "09:00", "end": "17:00"}}'', ',
           @doctor_user_id, ')'),
    'SELECT "Doctor profile already exists or user not found" as message'
);

PREPARE stmt FROM @create_doctor_profile;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 3. CREATE CLINIC ASSISTANT USER
-- =============================================================================

-- Check if clinic assistant user already exists
SET @assistant_exists = (
    SELECT COUNT(*) 
    FROM user 
    WHERE username = 'assistant' OR email = 'assistant@clinic.com'
);

-- Create or update clinic assistant user
SET @create_assistant = IF(@assistant_exists = 0,
    CONCAT('INSERT INTO user (username, email, name, password, roles, created_at, updated_at, is_active) VALUES ',
           '(''assistant'', ''assistant@clinic.com'', ''Mary Johnson'', ',
           '''$2y$13$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy'', ', -- Password: admin123
           '''[\"ROLE_ASSISTANT\"]'', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1)'),
    CONCAT('UPDATE user SET password = ''$2y$13$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy'', ', -- Password: admin123
           'roles = ''[\"ROLE_ASSISTANT\"]'', updated_at = CURRENT_TIMESTAMP, is_active = 1 ',
           'WHERE username = ''assistant'' OR email = ''assistant@clinic.com''')
);

PREPARE stmt FROM @create_assistant;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 4. ADDITIONAL SAMPLE USERS (Optional)
-- =============================================================================

-- Create additional doctor user for testing
SET @doctor2_exists = (
    SELECT COUNT(*) 
    FROM user 
    WHERE username = 'doctor2' OR email = 'doctor2@clinic.com'
);

SET @create_doctor2 = IF(@doctor2_exists = 0,
    CONCAT('INSERT INTO user (username, email, name, password, roles, created_at, updated_at, is_active) VALUES ',
           '(''doctor2'', ''doctor2@clinic.com'', ''Dr. Sarah Wilson'', ',
           '''$2y$13$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy'', ', -- Password: admin123
           '''[\"ROLE_DOCTOR\"]'', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1)'),
    CONCAT('UPDATE user SET password = ''$2y$13$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy'', ', -- Password: admin123
           'roles = ''[\"ROLE_DOCTOR\"]'', updated_at = CURRENT_TIMESTAMP, is_active = 1 ',
           'WHERE username = ''doctor2'' OR email = ''doctor2@clinic.com''')
);

PREPARE stmt FROM @create_doctor2;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create doctor2 profile
SET @doctor2_user_id = (SELECT id FROM user WHERE username = 'doctor2' LIMIT 1);
SET @doctor2_profile_exists = (
    SELECT COUNT(*) 
    FROM doctor 
    WHERE user_id = @doctor2_user_id
);

SET @create_doctor2_profile = IF(@doctor2_profile_exists = 0 AND @doctor2_user_id IS NOT NULL,
    CONCAT('INSERT INTO doctor (name, email, phone, specialization, license_number, working_hours, user_id) VALUES ',
           '(''Dr. Sarah Wilson'', ''doctor2@clinic.com'', ''+60123456788'', ''Pediatrics'', ''MD789012'', ',
           '''{"monday": {"start": "08:00", "end": "16:00"}, "tuesday": {"start": "08:00", "end": "16:00"}, "wednesday": {"start": "08:00", "end": "16:00"}, "thursday": {"start": "08:00", "end": "16:00"}, "friday": {"start": "08:00", "end": "16:00"}}'', ',
           @doctor2_user_id, ')'),
    'SELECT "Doctor2 profile already exists or user not found" as message'
);

PREPARE stmt FROM @create_doctor2_profile;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create additional assistant user for testing
SET @assistant2_exists = (
    SELECT COUNT(*) 
    FROM user 
    WHERE username = 'assistant2' OR email = 'assistant2@clinic.com'
);

SET @create_assistant2 = IF(@assistant2_exists = 0,
    CONCAT('INSERT INTO user (username, email, name, password, roles, created_at, updated_at, is_active) VALUES ',
           '(''assistant2'', ''assistant2@clinic.com'', ''Alice Brown'', ',
           '''$2y$13$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy'', ', -- Password: admin123
           '''[\"ROLE_ASSISTANT\"]'', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 1)'),
    CONCAT('UPDATE user SET password = ''$2y$13$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy'', ', -- Password: admin123
           'roles = ''[\"ROLE_ASSISTANT\"]'', updated_at = CURRENT_TIMESTAMP, is_active = 1 ',
           'WHERE username = ''assistant2'' OR email = ''assistant2@clinic.com''')
);

PREPARE stmt FROM @create_assistant2;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================================================
-- 5. VERIFICATION AND SUMMARY
-- =============================================================================

-- Display created users summary
SELECT 
    'USER CREATION SUMMARY' as message,
    (SELECT COUNT(*) FROM user WHERE JSON_CONTAINS(roles, '"ROLE_SUPER_ADMIN"')) as superadmin_count,
    (SELECT COUNT(*) FROM user WHERE JSON_CONTAINS(roles, '"ROLE_DOCTOR"')) as doctor_count,
    (SELECT COUNT(*) FROM user WHERE JSON_CONTAINS(roles, '"ROLE_ASSISTANT"')) as assistant_count,
    (SELECT COUNT(*) FROM user) as total_users;

-- Display all users with their roles
SELECT 
    id,
    username,
    email,
    name,
    roles,
    is_active,
    created_at
FROM user 
ORDER BY 
    CASE 
        WHEN JSON_CONTAINS(roles, '"ROLE_SUPER_ADMIN"') THEN 1
        WHEN JSON_CONTAINS(roles, '"ROLE_DOCTOR"') THEN 2
        WHEN JSON_CONTAINS(roles, '"ROLE_ASSISTANT"') THEN 3
        ELSE 4
    END,
    username;

-- Display doctor profiles
SELECT 
    d.id as doctor_id,
    d.name as doctor_name,
    d.email,
    d.specialization,
    d.license_number,
    u.username,
    u.is_active as user_active
FROM doctor d
LEFT JOIN user u ON d.user_id = u.id
ORDER BY d.name;

-- =============================================================================
-- 6. DEFAULT LOGIN CREDENTIALS
-- =============================================================================

SELECT 
    '=== DEFAULT LOGIN CREDENTIALS ===' as info,
    '' as spacer1,
    'SUPERADMIN:' as type1,
    '  Username: superadmin' as cred1,
    '  Password: admin123' as pass1,
    '  Email: superadmin@clinic.com' as email1,
    '' as spacer2,
    'DOCTOR:' as type2,
    '  Username: doctor' as cred2,
    '  Password: admin123' as pass2,
    '  Email: doctor@clinic.com' as email2,
    '' as spacer3,
    'CLINIC ASSISTANT:' as type3,
    '  Username: assistant' as cred3,
    '  Password: admin123' as pass3,
    '  Email: assistant@clinic.com' as email3,
    '' as spacer4,
    'Note: Change passwords after first login!' as warning;

SELECT 'USER CREATION DEPLOYMENT COMPLETED SUCCESSFULLY!' as final_message;