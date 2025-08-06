-- Debug login process - check all possible issues
USE clinic_db;

-- 1. Check if users exist with correct usernames
SELECT 'USER EXISTENCE CHECK' as test_type;
SELECT username, email, name, is_active, 
       LEFT(password, 30) as password_hash,
       roles
FROM user 
WHERE username IN ('superadmin', 'admin', 'dr.adhar', 'assistant1')
ORDER BY username;

-- 2. Check exact password hash for superadmin
SELECT 'SUPERADMIN PASSWORD CHECK' as test_type;
SELECT username, password, 
       (password = '$2y$13$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy') as is_admin123_hash
FROM user 
WHERE username = 'superadmin';

-- 3. Check if user is active
SELECT 'USER ACTIVE STATUS' as test_type;
SELECT username, is_active, created_at, updated_at
FROM user 
WHERE username = 'superadmin';

-- 4. Check roles format
SELECT 'ROLES FORMAT CHECK' as test_type;
SELECT username, roles, JSON_VALID(roles) as valid_json
FROM user 
WHERE username = 'superadmin';

-- 5. Create a test user with known working credentials
INSERT IGNORE INTO user (username, email, name, password, roles, created_at, updated_at, is_active) 
VALUES ('testuser', 'test@test.com', 'Test User', '$2y$13$M8eHJ8VWWJPCdKKpOzZhKuLqYGSFnqf8Bs5TkGVmH0LWtVmKG3rZy', '["ROLE_USER"]', NOW(), NOW(), 1);

-- 6. Final verification
SELECT 'FINAL TEST USER CHECK' as test_type;
SELECT username, email, password, roles, is_active
FROM user 
WHERE username = 'testuser';

SELECT 'LOGIN DEBUG COMPLETED' as status;