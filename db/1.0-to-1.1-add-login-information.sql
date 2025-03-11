-- Script to update the database schema from version 1.0 to 1.1
-- Adds login information fields to the trainers table

ALTER TABLE `trainers`
ADD COLUMN `password_hash` varchar(255) DEFAULT NULL COMMENT 'Hashed password for the trainer',
ADD COLUMN `reset_token` varchar(255) DEFAULT NULL COMMENT 'Password reset token',
ADD COLUMN `reset_expiration` datetime NOT NULL COMMENT 'Password reset token expiration time';
