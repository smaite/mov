-- Add google_id column to users table for Google OAuth integration
ALTER TABLE `users` 
ADD COLUMN `google_id` VARCHAR(50) DEFAULT NULL AFTER `profile_image`,
ADD INDEX `idx_google_id` (`google_id`);

-- Add last_login column to track last login time
ALTER TABLE `users` 
ADD COLUMN `last_login` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;