-- Migration: 1.1 to 1.2
-- Description: Add staff classification flags to operators table for ELOG routing

ALTER TABLE `operators`
  ADD COLUMN `is_eal_staff` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 if internal EAL staff member, 0 for outside users/clients' AFTER `status`,
  ADD COLUMN `is_senior_staff` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 if senior staff/lab management' AFTER `is_eal_staff`;
