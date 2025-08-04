-- Force update all user passwords to 'password'
USE clinic_db;

-- Update all existing users to have 'password' password
UPDATE user SET 
    password = '$2y$13$tarOU6Z5kowOY.blp0rkv.bR5GCxz9sn.uhaGe07aJ7H8XvYYfaWK',
    updated_at = CURRENT_TIMESTAMP,
    is_active = 1
WHERE username IN ('superadmin', 'doctor', 'assistant', 'dr.adhar', 'dr.sarah', 'assistant1', 'doctor2', 'assistant2');

-- Verify the update
SELECT username, 
       LEFT(password, 30) as password_hash,
       (password = '$2y$13$tarOU6Z5kowOY.blp0rkv.bR5GCxz9sn.uhaGe07aJ7H8XvYYfaWK') as is_password_hash,
       is_active
FROM user 
ORDER BY username;

SELECT 'PASSWORD UPDATE COMPLETED - All users now have password: password' as message;