<?php
/**
 * Main entry point for the OUAL Training Management System.
 *
 * This page provides an overview of the system and links to the main functionalities.
 * It includes database connection initialization and session handling.
 *
 * PHP version 5.4+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

session_start();

// Include the configuration and utility functions
require_once "config.php";
require_once "auth.php";

// Calculate the remaining session time for the countdown script
$timeUntilSessionExpires = getTimeUntilSessionExpires();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OUAL Operator Training Information</title>
    <link rel="stylesheet" href="dataTables.dataTables.css">
    <link rel="stylesheet" href="common.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="common.js" defer></script>
    <script>
        // Initialize the session expiration countdown
        document.addEventListener('DOMContentLoaded', () => {
            setCountdown(<?php echo $timeUntilSessionExpires; ?>);
        });
    </script>
</head>
<body>
    <?php require 'header.php'; ?>
    <div class="container">
        <h1>OUAL Training Information</h1>
        <h2>Operator Training Information Database</h2>
        <p>This is a complete greenfield re-write of the operators database that was created by John 
            O'Donnel and maintained by Donald Carter.</p>
        <p>This version was started in November of 2024 by Gregory Leblanc. It was developed using 
            ChatGPT, among other resources.</p>
        <p>The basic function of the operator training information database and related web pages 
            is to record an association between a person (operator) and a certification, indicating 
            that the person has completed all the training required to obtain the certification.</p>

        <!-- Links to relevant pages and their descriptions -->
        <p>The main list of current EAL personnel can be found at:
            <a href="personnel_list.php">personnel_list.php</a></p>
        <p>The list of all personnel including those no longer active at EAL can be found at:
            <a href="personnel_list_all.php">personnel_list_all.php</a></p>
        <p>The list of personnel who can give each certification sorted by trainer can be found at:
            <a href="trainer_list.php">trainer_list.php</a></p>
        <p>The list of personnel who can give each certification sorted by certification can be found at:
            <a href="trainer_list_by_cert.php">trainer_list_by_cert.php</a></p>
        <p>New personnel can be added at:
            <a href="personnel_add.php">personnel_add.php</a></p>
        <p>New trainers can be added at:
            <a href="trainer_add.php">trainer_add.php</a></p>
        <p>Login at:
            <a href="login.php">login.php</a></p>
        <p>Logout at:
            <a href="logout.php">logout.php</a></p>

        <p>New trainings can be registered using <b>certification_add.php</b>, but don't access that page directly. 
            Instead, go to edit a user and add a certification from there.</p>

        <!-- Description of the database tables and their purposes -->
        <p>This information is recorded in a database table named <b>optraining</b> and kept on the 
        localhost system using MySQL. The optraining table links an operator and a certification, and 
        also includes additional information on who is responsible for the training, a status (usually 
        <i>Active</i>), an expiration date (usually not used), and a timestamp showing when the completed 
        training was entered in the table. This information is usually accessed using the <i>List 
        Completed Certifications</i> button on the main operator training web page, and certifications 
        are added to the database using the <i>Add completed training certification</i> button (actually
        you select an operator from a list of names, then press a button).</p>
        <p>Another table, <b>certifications</b>, contains a list of available certifications. This 
        table includes a long and short form name for the certification, a comment field, and an 
        expiration field showing the number of months a certification is valid (usually not used, 
        indicating the certification does not expire).</p>
        <p>Another table, <b>operators</b>, contains information about the people being trained, 
        including name, phone numbers, and office information. Other tables referring to operators contain 
        pointers into this table (rather than operator names).</p>
        <p>A table, <b>trainers</b>, contains a list of browser login names (which are different from 
        the names used in the operators table) allowed to record completion of training 
        certification.</p>
        <p>A table, <b>can_certify</b>, links trainers to certifications, showing which trainers are 
        allowed to certify what training.</p>
        <p>Most everyday transactions should be accomplished using the appropriate web pages. Some 
        advanced operations are done manually using the MySQL database interface on localhost.</p>
        <p>Please report problems or suggestions to Gregory Leblanc</p>
    </div>
</body>
</html>
