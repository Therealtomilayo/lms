-- Migration: 0038_add_avatar_url_to_users_table
-- Description: Adds avatar_url to users table for passport photograph profile images

ALTER TABLE `users`
    ADD COLUMN `avatar_url` VARCHAR(255) NULL AFTER `phone`;
