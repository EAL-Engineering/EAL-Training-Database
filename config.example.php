<?php
/**
 * Database configuration and utility functions for the Training Management System.
 *
 * This file contains database connection settings and helper functions,
 * including session expiration calculations.
 *
 * PHP Version 5.4+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

// Basic database connection settings
$databaseHost = 'localhost'; // e.g., localhost or 127.0.0.1
$databaseUsername = 'your_database_user';
$databasePassword = 'your_database_password';
$databaseName = 'your_database_name';

// Enable error reporting for MySQLi
// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_report(MYSQLI_REPORT_ERROR);

// Connect to the database
$mysqli = new mysqli($databaseHost, $databaseUsername, $databasePassword, $databaseName);

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    echo "<br/>";
    echo "Error number: " . $mysqli->connect_errno;
    exit();
}