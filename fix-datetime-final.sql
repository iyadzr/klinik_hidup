-- Fix all invalid datetime values
SET sql_mode = 'ALLOW_INVALID_DATES';

-- Fix medication table
UPDATE medication SET updated_at = CURRENT_TIMESTAMP WHERE updated_at = '0000-00-00 00:00:00';
UPDATE medication SET created_at = CURRENT_TIMESTAMP WHERE created_at = '0000-00-00 00:00:00';

-- Fix user table  
UPDATE user SET created_at = CURRENT_TIMESTAMP WHERE created_at = '0000-00-00 00:00:00';
UPDATE user SET updated_at = CURRENT_TIMESTAMP WHERE updated_at = '0000-00-00 00:00:00';

-- Fix consultation table
UPDATE consultation SET created_at = CURRENT_TIMESTAMP WHERE created_at = '0000-00-00 00:00:00';

-- Fix setting table
UPDATE setting SET created_at = CURRENT_TIMESTAMP WHERE created_at = '0000-00-00 00:00:00';
UPDATE setting SET updated_at = CURRENT_TIMESTAMP WHERE updated_at = '0000-00-00 00:00:00';

-- Fix queue table
UPDATE queue SET updated_at = CURRENT_TIMESTAMP WHERE updated_at = '0000-00-00 00:00:00';

-- Reset SQL mode
SET sql_mode = 'TRADITIONAL';

SELECT 'All invalid datetime values have been fixed' as message;