--
-- Training Management System / Operator Database Schema
--

--
-- Table structure for table `annualradsafety`
--

DROP TABLE IF EXISTS `annualradsafety`;
CREATE TABLE `annualradsafety` (
  `seq_nmbr` int(11) NOT NULL AUTO_INCREMENT,
  `op_ptr` int(11) NOT NULL,
  `trainer_ptr` int(11) NOT NULL,
  `training_date` date NOT NULL,
  `expires` date DEFAULT NULL,
  `entered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `entered_by` int(11) DEFAULT NULL,
  `status` enum('Complete','InProgress','Suspended','Canceled','Other') DEFAULT NULL,
  `course` tinytext,
  `comment` tinytext,
  PRIMARY KEY (`seq_nmbr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `can_certify`
--
-- Relationships:
--   can_certify.trainer_ptr -> operators.seq_nmbr
--   can_certify.cert_ptr    -> certifications.seq_nmbr
--

DROP TABLE IF EXISTS `can_certify`;
CREATE TABLE `can_certify` (
  `seq_nmbr` int(11) NOT NULL AUTO_INCREMENT,
  `trainer_ptr` int(11) NOT NULL COMMENT 'Foreign key to operators.seq_nmbr',
  `cert_ptr` int(11) NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `comment` tinytext,
  PRIMARY KEY (`seq_nmbr`),
  KEY `fk_tbl.can_certify_cert_ptr_tbl.certifications_seq_nmbr` (`cert_ptr`),
  KEY `fk_tbl.can_certify_trainer_ptr_tbl.operators_seq_nmbr` (`trainer_ptr`),
  CONSTRAINT `fk_can_certify_cert_ptr` FOREIGN KEY (`cert_ptr`) REFERENCES `certifications` (`seq_nmbr`),
  CONSTRAINT `fk_can_certify_trainer_ptr` FOREIGN KEY (`trainer_ptr`) REFERENCES `operators` (`seq_nmbr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `certifications`
--

DROP TABLE IF EXISTS `certifications`;
CREATE TABLE `certifications` (
  `seq_nmbr` int(11) NOT NULL AUTO_INCREMENT,
  `certification` tinytext NOT NULL,
  `short` tinytext,
  `exp_months` int(11) DEFAULT NULL,
  `comment` tinytext,
  PRIMARY KEY (`seq_nmbr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `operators`
--

DROP TABLE IF EXISTS `operators`;
CREATE TABLE `operators` (
  `seq_nmbr` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `altemail` varchar(255) DEFAULT NULL,
  `phones` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive','Other') DEFAULT NULL,
  `office` varchar(255) DEFAULT NULL,
  `home` varchar(255) DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `comments` varchar(255) DEFAULT NULL,
  `entered` timestamp NULL DEFAULT NULL,
  `addedby` tinytext,
  PRIMARY KEY (`seq_nmbr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `optraining`
--

DROP TABLE IF EXISTS `optraining`;
CREATE TABLE `optraining` (
  `seq_nmbr` int(11) NOT NULL AUTO_INCREMENT,
  `operator` int(11) NOT NULL,
  `certification` int(11) NOT NULL,
  `trainer` int(11) NOT NULL,
  `status` enum('Active','Expired','Suspended','Revoked','Pending','Other') DEFAULT NULL,
  `entered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires` date DEFAULT NULL,
  PRIMARY KEY (`seq_nmbr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `trainers`
--
-- A trainer is an operator who has login credentials and can certify others.
-- trainers.optbl_ptr links back to the operators record for the person.
-- Note: can_certify.trainer_ptr points to operators.seq_nmbr (NOT trainers.seq_nmbr).
-- To look up a trainer's login/role from can_certify, join:
--   can_certify.trainer_ptr = operators.seq_nmbr = trainers.optbl_ptr
--

DROP TABLE IF EXISTS `trainers`;
CREATE TABLE `trainers` (
  `seq_nmbr` int(11) NOT NULL AUTO_INCREMENT,
  `login_name` tinytext NOT NULL,
  `optbl_ptr` int(11) DEFAULT '-1' COMMENT 'Foreign key to operators.seq_nmbr',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `comment` tinytext,
  `role_id` tinyint(4) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiration` datetime DEFAULT NULL,
  PRIMARY KEY (`seq_nmbr`),
  UNIQUE KEY `fk_tbl.trainers_optbl_ptr_tbl.operators_seq_nmbr` (`optbl_ptr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
