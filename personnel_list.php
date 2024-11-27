<?php
// Start the session at the beginning of the page
session_start();

// Include the database connection file
include_once("config.php");

$timeUntilSessionExpires = getTimeUntilSessionExpires();

// Fetch operator list
$opertor_list = $mysqli->query("
    SELECT 
        o.name AS OperatorName, 
        o.email AS OperatorEmail, 
        o.seq_nmbr as id,
        c.certification AS HighestCertification 
    FROM operators o 
    JOIN optraining ot ON o.seq_nmbr = ot.operator 
    JOIN certifications c ON ot.certification = c.seq_nmbr 
    WHERE o.status = 'Active' 
    AND c.seq_nmbr = ( 
        SELECT MAX(inner_c.seq_nmbr) 
        FROM optraining inner_ot 
        JOIN certifications inner_c ON inner_ot.certification = inner_c.seq_nmbr 
        WHERE inner_ot.operator = o.seq_nmbr 
        AND inner_c.seq_nmbr <= 3 
    ) 
    ORDER BY o.name
    ");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Personnel List</title>
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
    <div class="header">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span>Logged in as: <?php echo htmlspecialchars($_SESSION['fname']); ?></span> |
            <span>Session expires in: <span id="countdown"></span></span>
            <a href="logout.php" class="logout-button">Logout</a>
        <?php else: ?>
            <span>Welcome to the OUAL Training Information Portal</span>
        <?php endif; ?>
    </div>

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
                <th>Certification</th>
                <th>User</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $rowCounter = 0; // Unique ID for each row's email
        while ($res = mysqli_fetch_array($opertor_list)) {
            $email = explode('@', $res['OperatorEmail']);
            $user = $email[0];
            $domain = $email[1];
            $rowId = "email-" . $rowCounter++; // Generate a unique ID
            echo "<tr>";
            echo "<td>" . htmlspecialchars($res['OperatorName']) . "</td>\n";
            echo "<td id='" . $rowId . "' data-user='" . htmlspecialchars($user) . "' data-domain='" . htmlspecialchars($domain) . "'></td>\n";
            echo "<td>" . htmlspecialchars($res['HighestCertification']) . "</td>\n";
            echo "<td><a href=\"personnel_edit.php?id=" . htmlspecialchars($res['id']) . "\">Edit</a></td>\n";
            echo "</tr>";
        }
        ?>
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
