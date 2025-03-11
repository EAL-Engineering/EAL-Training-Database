-- ========================================================
-- Tandem Accelerator Database Schema
-- ========================================================
-- Version: 1.0
-- Last updated: 2025-03-11
-- 
-- This schema defines the structure for a particle accelerator
-- management system, tracking operators, certifications,
-- and training.
-- ========================================================

-- --------------------------------------------------------
-- Table: operators
-- --------------------------------------------------------
-- Stores information about accelerator operators/personnel
CREATE TABLE `operators` (
  `seq_nmbr` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL COMMENT 'Username/login name',
  `fname` varchar(255) NOT NULL COMMENT 'Full name of the operator',
  `email` varchar(255) DEFAULT NULL COMMENT 'Primary email address',
  `altemail` varchar(255) DEFAULT NULL COMMENT 'Secondary email address',
  `phones` varchar(255) DEFAULT NULL COMMENT 'Contact phone numbers',
  `status` enum('Active','Inactive','Other') DEFAULT NULL COMMENT 'Current operator status',
  `office` varchar(255) DEFAULT NULL COMMENT 'Office location',
  `home` varchar(255) DEFAULT NULL COMMENT 'Home address',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last record update time',
  `comments` varchar(255) DEFAULT NULL COMMENT 'Additional notes about the operator',
  `entered` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'When the operator was first added',
  `addedby` tinytext COMMENT 'Who added this operator record',
  PRIMARY KEY (`seq_nmbr`) COMMENT 'Unique operator identifier'
) COMMENT 'Personnel authorized to operate the accelerator';

-- --------------------------------------------------------
-- Table: certifications
-- --------------------------------------------------------
-- Lists different types of certifications that operators can obtain
CREATE TABLE `certifications` (
  `seq_nmbr` int(11) NOT NULL AUTO_INCREMENT,
  `certification` tinytext NOT NULL COMMENT 'Name of the certification',
  `short` tinytext COMMENT 'Abbreviated name of certification',
  `exp_months` int(11) DEFAULT NULL COMMENT 'Certification Validity Length (in months)',
  `comment` tinytext COMMENT 'Additional notes about this certification type',
  PRIMARY KEY (`seq_nmbr`) COMMENT 'Unique certification identifier'
) COMMENT 'Types of certifications required for accelerator operation';

-- --------------------------------------------------------
-- Table: trainers
-- --------------------------------------------------------
-- Personnel authorized to train and certify operators
CREATE TABLE `trainers` (
  `seq_nmbr` int(11) NOT NULL AUTO_INCREMENT,
  `login_name` tinytext NOT NULL COMMENT 'Username of the trainer (always the part of the email before the @)',
  `optbl_ptr` int(11) DEFAULT '-1' COMMENT 'Foreign key to operators table',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When trainer status was granted',
  `comment` tinytext COMMENT 'Additional notes about this trainer',
  PRIMARY KEY (`seq_nmbr`),
  UNIQUE KEY `fk_tbl.trainers_optbl_ptr_tbl.operators_seq_nmbr` (`optbl_ptr`) COMMENT 'Each operator can have at most one trainer record'
) COMMENT 'Personnel authorized to certify and train other operators';

-- --------------------------------------------------------
-- Table: optraining
-- --------------------------------------------------------
-- Tracks the certifications held by each operator
CREATE TABLE `optraining` (
  `seq_nmbr` int(11) NOT NULL AUTO_INCREMENT,
  `operator` int(11) NOT NULL COMMENT 'Foreign key to operators.seq_nmbr',
  `certification` int(11) NOT NULL COMMENT 'Foreign key to certifications.seq_nmbr',
  `trainer` int(11) NOT NULL COMMENT 'Foreign key to trainers.seq_nmbr',
  `status` enum('Active','Expired','Suspended','Revoked','Pending','Other') DEFAULT NULL COMMENT 'Current status of certification',
  `entered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When this record was last updated',
  `expires` date DEFAULT NULL COMMENT 'When this certification expires',
  PRIMARY KEY (`seq_nmbr`)
) COMMENT 'Junction table linking operators to their obtained certifications';

-- --------------------------------------------------------
-- Table: can_certify
-- --------------------------------------------------------
-- Defines which certifications a trainer can issue
CREATE TABLE `can_certify` (
  `seq_nmbr` int(11) NOT NULL AUTO_INCREMENT,
  `trainer_ptr` int(11) NOT NULL COMMENT 'Foreign key to trainers.seq_nmbr',
  `cert_ptr` int(11) NOT NULL COMMENT 'Foreign key to certifications.seq_nmbr',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When this authorization was granted',
  `comment` tinytext COMMENT 'Additional notes about this authorization',
  PRIMARY KEY (`seq_nmbr`),
  KEY `fk_tbl.can_certify_cert_ptr_tbl.certifications_seq_nmbr` (`cert_ptr`)
) COMMENT 'Defines which certifications each trainer is authorized to issue';

-- --------------------------------------------------------
-- Table: annualradsafety
-- --------------------------------------------------------
-- Tracks radiation safety training required annually
CREATE TABLE `annualradsafety` (
  `seq_nmbr` int(11) NOT NULL AUTO_INCREMENT,
  `op_ptr` int(11) NOT NULL COMMENT 'Foreign key to operators.seq_nmbr',
  `trainer_ptr` int(11) NOT NULL COMMENT 'Foreign key to trainers.seq_nmbr',
  `training_date` date NOT NULL COMMENT 'When training was completed',
  `expires` date DEFAULT NULL COMMENT 'When training certification expires',
  `entered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record creation/update timestamp',
  `entered_by` int(11) DEFAULT NULL COMMENT 'Foreign key to operators.seq_nmbr who entered this record',
  `status` enum('Complete','InProgress','Suspended','Canceled','Other') DEFAULT NULL COMMENT 'Current status of the training',
  `course` tinytext COMMENT 'Specific radiation safety course taken',
  `comment` tinytext COMMENT 'Additional notes about this training',
  PRIMARY KEY (`seq_nmbr`)
) COMMENT 'Annual radiation safety training records for operators';

-- --------------------------------------------------------
-- Foreign Key Relationships (documented but not enforced)
-- --------------------------------------------------------
-- 1. trainers.optbl_ptr -> operators.seq_nmbr
-- 2. optraining.operator -> operators.seq_nmbr
-- 3. optraining.certification -> certifications.seq_nmbr
-- 4. optraining.trainer -> trainers.seq_nmbr
-- 5. can_certify.trainer_ptr -> trainers.seq_nmbr
-- 6. can_certify.cert_ptr -> certifications.seq_nmbr
-- 7. annualradsafety.op_ptr -> operators.seq_nmbr
-- 8. annualradsafety.trainer_ptr -> trainers.seq_nmbr
-- 9. annualradsafety.entered_by -> operators.seq_nmbr