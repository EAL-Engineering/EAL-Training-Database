<?php
// Include the database connection file
include_once("config.php");

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
    <title>EAL Stuff</title>
    <link rel="stylesheet" href="dataTables.dataTables.css">
    <link rel="icon" type="image/svg+xml" href="EALlogoZM.svg">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <style>
        .form-container { max-width: 600px; margin: 5px auto; padding: 5px;}
        .back-button-container { margin-top: 5px; margin-bottom: 5px; text-align: center; }
        .back-button-container a { 
            display: inline-block; 
            padding: 10px 20px; 
            text-decoration: none; 
            color: white; 
            background-color: #007bff; 
            border-radius: 4px; 
            transition: background-color 0.2s ease; 
            margin-left: 20px;
            margin-right: 20px; 
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="back-button-container">
            <a href="personnel_list_all.php">To ALL Personnel List</a>
            <a href="index.php">To main page</a>
        </div>
    </div>
    <table id="personnel" class="display">
        <thead>
            <tr>
                <td>Full Name</td>
                <td>Email</td>
                <td>Certification</td>
                <td>User</td>
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
