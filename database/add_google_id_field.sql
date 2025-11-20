-- Add google_id field to users table if it doesn't exist
ALTER TABLE users ADD COLUMN google_id VARCHAR(255) UNIQUE NULL AFTER password;
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER updated_at;
