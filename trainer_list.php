<?php
// Start the session at the beginning of the page
session_start();

// Include the database connection file
include_once("config.php");

$timeUntilSessionExpires = getTimeUntilSessionExpires();

// Fetch trainer list and their certifications
$trainer_list = $mysqli->query("
    SELECT 
        o.fname AS TrainerName, 
        o.email AS TrainerEmail, 
        o.seq_nmbr AS id, 
        GROUP_CONCAT(c.certification SEPARATOR ', ') AS Certifications 
    FROM 
        operators o 
        JOIN can_certify cc ON o.seq_nmbr = cc.trainer_ptr 
        JOIN certifications c ON cc.cert_ptr = c.seq_nmbr 
    WHERE 
        o.status = 'Active' 
    GROUP BY 
        o.seq_nmbr 
");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trainer List</title>
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
    <?php include 'header.php'; ?>
    </div>
    <div class="form-container">
        <div class="back-button-container">
            <a href="trainer_list_by_cert.php">To Trainers by Certification</a>
            <a href="index.php">To main page</a>
        </div>
    </div>  

    <h1>Trainer List</h1>

    <table id="trainer" class="display">
        <thead>
            <tr>
                <td>Full Name</td>
                <td>Email</td>
                <td>Certifications</td>
                <td>Actions</td>
            </tr>
        </thead>
        <tbody>
        <?php
        $rowCounter = 0; // Unique ID for each row's email
        while ($res = mysqli_fetch_array($trainer_list)) {
            $email = explode('@', $res['TrainerEmail']);
            $user = $email[0];
            $domain = $email[1];
            $rowId = "email-" . $rowCounter++; // Generate a unique ID
            echo "<tr>";
            echo "<td>" . htmlspecialchars($res['TrainerName']) . "</td>\n";
            echo "<td id='" . $rowId . "' data-user='" . htmlspecialchars($user) . "' data-domain='" . htmlspecialchars($domain) . "'></td>\n";
            echo "<td>" . htmlspecialchars($res['Certifications']) . "</td>\n";
            echo "<td><a href=\"trainer_edit.php?id=" . htmlspecialchars($res['id']) . "\">Edit</a></td>\n";
            echo "</tr>";
        }
        ?>
        </tbody>
    </table>

    <script>
        $(document).ready(function() {
            // DataTable initialization
            new DataTable('#trainer', {
                scrollX: true,
                pageLength: 15,
                lengthMenu: [10, 15, 25, 50, 75, 100]
            });

            // Populate email links
            document.querySelectorAll('td[id^="email-"]').forEach(cell => {
                const user = cell.getAttribute('data-user');
                const domain = cell.getAttribute('data-domain');
                const email = `${user}@${domain}`;
                const link = document.createElement('a');
                link.href = `mailto:${email}`;
                link.textContent = email;
                cell.appendChild(link);
            });
        });
    </script>
</body>
</html>
