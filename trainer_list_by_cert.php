<?php
// Include the database connection file
include_once("config.php");

// Fetch certifications and their trainers
$certification_list = $mysqli->query("
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
");
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Trainers by Certification</title>
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
				<td>Actions</td>
			</tr>
		</thead>
		<tbody>
		<?php
		while ($res = mysqli_fetch_array($certification_list)) {
			echo "<tr>";
			echo "<td>" . htmlspecialchars($res['CertificationName']) . "</td>\n";
			echo "<td>" . htmlspecialchars($res['Trainers'] ?: 'No active trainers') . "</td>\n";
			echo "<td><a href=\"certification_edit.php?id=" . htmlspecialchars($res['CertID']) . "\">Edit Certification</a></td>\n";
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
