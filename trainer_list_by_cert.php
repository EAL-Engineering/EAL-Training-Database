<?php
/**
 * Trainer List by Certification Page
 *
 * Fetches and displays trainers grouped by their certifications from the database.
 * 
 * PHP version 5.4+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

// Start the session at the beginning of the page
session_start();

// Include the database connection file
require_once "config.php";

/**
 * Gets the time until the session expires.
 *
 * @return int Time in seconds until the session expires.
 */
$timeUntilSessionExpires = getTimeUntilSessionExpires();

/**
 * Fetch certifications and their associated trainers.
 *
 * The query retrieves active trainers grouped by their certifications.
 *
 * @global mysqli $mysqli The database connection object.
 *
 * @return mysqli_result|false The result set object or false on failure.
 */
$certification_list = $mysqli->query(
    "
    SELECT 
        c.certification AS CertificationName, 
        c.seq_nmbr AS CertID,
        GROUP_CONCAT(o.fname SEPARATOR ', ') AS Trainers 
    FROM 
        certifications c 
        LEFT JOIN can_certify cc ON c.seq_nmbr = cc.cert_ptr 
        LEFT JOIN operators o ON cc.trainer_ptr = o.seq_nmbr 
    WHERE 
        o.status = 'Active'
    GROUP BY 
        c.seq_nmbr
"
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trainers by Certification</title>
    <link rel="stylesheet" href="dataTables.dataTables.css">
    <link rel="stylesheet" href="common.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="common.js" defer></script>
    <script>
        // Pass the session expiration time to the JavaScript function
        document.addEventListener('DOMContentLoaded', () => {
            setCountdown(<?php echo $timeUntilSessionExpires; ?>);
        });
    </script>
</head>
<body>
    <?php require 'header.php'; ?>
    <div class="form-container">
        <div class="back-button-container">
            <a href="trainer_list.php">To Trainers List</a>
            <a href="index.php">To main page</a>
        </div>
    </div>
    <h1>Trainers by Certification</h1>
    <table id="certifications" class="display">
        <thead>
            <tr>
                <td>Certification</td>
                <td>Trainers</td>
            </tr>
        </thead>
        <tbody>
        <?php
        // Iterate through the result set to populate the table
        while ($res = mysqli_fetch_array($certification_list)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($res['CertificationName']) . "</td>\n";
            echo "<td>" . htmlspecialchars($res['Trainers'] ?: 'No active trainers') . "</td>\n";
            echo "</tr>";
        }
        ?>
        </tbody>
    </table>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            new DataTable('#certifications', {
                scrollX: true,
                pageLength: 15,
                lengthMenu: [10, 15, 25, 50, 75, 100]
            });
        });
    </script>
</body>
</html>
