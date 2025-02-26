<?php
/**
 * Delete a personnel entry from all related tables
 * 
 * PHP version 5.4+
 *
 * @category Certification
 * @package  TrainingManagementSystem
 * @author   Gregory Leblanc <leblanc+php@ohio.edu>
 * @license  AGPLv3 http://www.gnu.org/licenses/agpl-3.0.html
 * @link     https://inpp.ohio.edu/~leblanc/eal_2024
 */

// Start the session
session_start();

// Include configuration and helper files
require_once "config.php";

/**
 * Encoded URL string of the current page for safe use in GET parameters.
 * 
 * @var string $currentUrl
 */
$currentUrl = urlencode($_SERVER['REQUEST_URI']);

// Ensure user is logged in and has the correct role
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] > 2) {
    header("Location: login.php?return=$currentUrl");
    exit();
}

/**
 * Get the time remaining until the user's session expires.
 *
 * @var int $timeUntilSessionExpires Time in seconds until the session expires.
 */
$timeUntilSessionExpires = getTimeUntilSessionExpires();

// Handle deletion request
if (isset($_GET['id']) && isset($_GET['confirm']) && $_GET['confirm'] == 1) {
    $id = intval($_GET['id']);
    
    $mysqli->autocommit(false);
    
    $deleteSuccess = true;
    $deleteSuccess &= $mysqli->query("DELETE FROM annualradsafety WHERE op_ptr = $id");
    $deleteSuccess &= $mysqli->query("DELETE FROM optraining WHERE operator = $id");
    $deleteSuccess &= $mysqli->query("DELETE FROM trainers WHERE optbl_ptr = $id");
    $deleteSuccess &= $mysqli->query("DELETE FROM operators WHERE seq_nmbr = $id");
    
    if ($deleteSuccess) {
        $mysqli->commit();
    } else {
        $mysqli->rollback();
    }
    
    header("Location: personnel_delete.php");
    exit();
}

// Fetch personnel list
$result = $mysqli->query("
SELECT 
    o.seq_nmbr AS id, 
    o.name AS OperatorName, 
    o.email AS OperatorEmail,
    (
        SELECT 
            c.certification 
        FROM
            optraining ot 
        JOIN 
            certifications c ON ot.certification = c.seq_nmbr
        WHERE 
            ot.operator = o.seq_nmbr 
        ORDER BY 
            c.seq_nmbr DESC LIMIT 1
    ) 
    AS HighestCertification
    FROM 
        operators o 
    WHERE 
        o.status = 'Active' 
    ORDER BY 
    o.name
"
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Personnel</title>
    <link rel="stylesheet" href="dataTables.dataTables.css">
    <link rel="stylesheet" href="common.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="common.js" defer></script>
    <script>
        function confirmDeletion(id, name) {
            var userInput = prompt("Type 'YES' (all caps) to confirm deletion of " + name + ":");
            if (userInput !== null && userInput === "YES") {
                window.location.href = "personnel_delete.php?id=" + id + "&confirm=1";
            } else {
                alert("Deletion canceled.");
            }
        };
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
            <a href="personnel_list_all.php">To ALL Personnel List</a>
            <a href="index.php">To main page</a>
        </div>
    </div>
    <table id="personnel" class="display">
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Email</th>
                <?php if ($_SESSION['role_id'] <= 2) : ?>
                    <th>Delete</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php while ($res = mysqli_fetch_array($result)) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($res['OperatorName']); ?></td>
                    <td><?php echo '<a href="mailto:' . htmlspecialchars($res['OperatorEmail']) . '">' . htmlspecialchars($res['OperatorEmail']) . '</a>'; ?></td>
                    <?php if ($_SESSION['role_id'] <= 2) : ?>
                        <td>
                            <button onclick="confirmDeletion(<?php echo $res['id']; ?>, '<?php echo htmlspecialchars(addslashes($res['OperatorName'])); ?>')">Delete</button>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <script>
        $(document).ready(function() {
            // Populate email links first
            document.querySelectorAll('td[id^="email-"]').forEach(cell => {
                const user = cell.getAttribute('data-user');
                const domain = cell.getAttribute('data-domain');
                const email = `${user}@${domain}`;
                const link = document.createElement('a');
                link.href = `mailto:${email}`;
                link.textContent = email;
                cell.appendChild(link);
            });

            // Now initialize the DataTable after the content is populated
            new DataTable('#personnel', {
                scrollX: true,
                pageLength: 15,
                lengthMenu: [10, 15, 25, 50, 75, 100]
            });
        });

    </script>
</body>
</html>
