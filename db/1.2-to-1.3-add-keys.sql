-- ========================================================
-- Key Tracking Schema Update
-- ========================================================
-- Version: 1.3
-- Last updated: 2026-07-21
-- 
-- Adds operator_keys table to track lab restricted area access
-- via university badges, student keys (200A21), and master keys (200A2).
-- ========================================================

-- --------------------------------------------------------
-- Table: operator_keys
-- --------------------------------------------------------
-- Tracks keys assigned to operators, including history.
-- When a key is returned, status becomes 'Returned' with returned_date set.
-- When keys are replaced during re-keying, status becomes 'Obsolete'.
CREATE TABLE `operator_keys` (
  `seq_nmbr` int(11) NOT NULL AUTO_INCREMENT,
  `operator_id` int(11) NOT NULL COMMENT 'Foreign key to operators.seq_nmbr',
  `key_type` varchar(16) NOT NULL COMMENT 'badge, 200A2, 200A21, or future re-key number',
  `serial_number` varchar(16) NOT NULL COMMENT 'Key serial number',
  `status` enum('Active','Lost','Returned','Obsolete') NOT NULL DEFAULT 'Active' COMMENT 'Current status of the key',
  `issued_date` date DEFAULT NULL COMMENT 'When the key was issued',
  `returned_date` date DEFAULT NULL COMMENT 'When the key was returned',
  `notes` tinytext COMMENT 'Additional notes about this key assignment',
  `replaced_by_seq_nmbr` int(11) DEFAULT NULL COMMENT 'Points to replacement key record after re-keying',
  `entered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When record was created',
  `entered_by` tinytext COMMENT 'Who entered this record',
  PRIMARY KEY (`seq_nmbr`),
  KEY `fk_operator_id` (`operator_id`),
  KEY `fk_replaced_by` (`replaced_by_seq_nmbr`)
) COMMENT='Tracks keys assigned to operators, including history';

-- Foreign Key Relationships (documented but not enforced)
-- 1. operator_keys.operator_id -> operators.seq_nmbr
-- 2. operator_keys.replaced_by_seq_nmbr -> operator_keys.seq_nmbr (self-reference)
