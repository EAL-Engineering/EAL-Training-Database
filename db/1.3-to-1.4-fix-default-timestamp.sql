-- Migration: 1.3 to 1.4
-- Description: Fix incompatible '0000-00-00 00:00:00' default timestamp in operators table for strict SQL modes

ALTER TABLE `operators`
  MODIFY COLUMN `entered` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the operator was first added';
