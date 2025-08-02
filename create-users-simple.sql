-- Create default users for clinic management system
-- Password: 'password' (hashed with bcrypt)

-- Note: The password hash below is for 'password' using bcrypt
-- You can generate new hashes using: php -r "echo password_hash('password', PASSWORD_DEFAULT);"

INSERT IGNORE INTO `user` (
    `username`, 
    `email`, 
    `name`, 
    `password`, 
    `roles`, 
    `allowed_pages`, 
    `is_active`, 
    `created_at`, 
    `updated_at`
) VALUES 
(
    'superadmin',
    'superadmin@clinic.com',
    'Super Admin',
    '$2y$13$2wuGXJ8LM2oHKr.qwxY8LubE6ZeC9SWQVJgJzXlv8/Fhq3y8HXJ3O',
    '["ROLE_SUPER_ADMIN","ROLE_ADMIN","ROLE_USER"]',
    '["dashboard","patients","doctors","queue","consultations","payments","medicines","users","settings","reports"]',
    1,
    NOW(),
    NOW()
),
(
    'doctor',
    'doctor@clinic.com',
    'Dr. Default',
    '$2y$13$2wuGXJ8LM2oHKr.qwxY8LubE6ZeC9SWQVJgJzXlv8/Fhq3y8HXJ3O',
    '["ROLE_DOCTOR","ROLE_USER"]',
    '["dashboard","queue","consultations","patients","medicines"]',
    1,
    NOW(),
    NOW()
),
(
    'assistant',
    'assistant@clinic.com',
    'Clinic Assistant',
    '$2y$13$2wuGXJ8LM2oHKr.qwxY8LubE6ZeC9SWQVJgJzXlv8/Fhq3y8HXJ3O',
    '["ROLE_ASSISTANT","ROLE_USER"]',
    '["dashboard","queue","patients","payments"]',
    1,
    NOW(),
    NOW()
);