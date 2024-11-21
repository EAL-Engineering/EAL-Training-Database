<?php
// Include the database connection file
include_once("config.php");

// Fetch operator list
// $opertor_list = $mysqli -> query("SELECT * FROM operators WHERE status='Active' ORDER BY name");
$opertor_list = $mysqli -> query("SELECT o.name AS OperatorName, o.status AS OperatorStatus, o.email as OperatorEmail, c.certification AS HighestCertification FROM operators o JOIN optraining ot ON o.seq_nmbr = ot.operator JOIN certifications c ON ot.certification = c.seq_nmbr WHERE o.status = 'Active' AND c.seq_nmbr = ( SELECT MAX(inner_c.seq_nmbr) FROM optraining inner_ot JOIN certifications inner_c ON inner_ot.certification = inner_c.seq_nmbr WHERE inner_ot.operator = o.seq_nmbr AND inner_c.seq_nmbr <= 3 ) ORDER BY o.name");

function obfuscateEmail($email) {
    $parts = explode('@', $email);
    if (count($parts) === 2) {
        $localPart = substr($parts[0], 0, 2) . str_repeat('*', max(0, strlen($parts[0]) - 2));
        $domain = $parts[1];
	}
}

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>EAL Stuff</title>
	<link rel="stylesheet" href="dataTables.dataTables.css">
	<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
  	<script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
</head>
<body>
	<table id="personnel" class="display">
		<thead>
			<tr>
				<td>Full Name</td>
				<td>Status</td>
				<td>email</td>
				<td>Certification</td>
				<td>User</td>
			</tr>
		</thead>
		<tbody>
		<?php
		while($res = mysqli_fetch_array($opertor_list)) {
			if ($res['seq_nmbr']) {}
			echo "<tr>";
			echo "<td>".$res['OperatorName']."</td>";
			echo "<td>".$res['OperatorStatus']."</td>";
			echo "<td>".$res['OperatorEmail']."</td>";
			echo "<td>".$res['HighestCertification']."</td>";			
			echo "<td><a href=\"edit.php?id=$res[id]\">Edit</a> | <a href=\"delete.php?id=$res[id]\" onClick=\"return confirm('Are you sure you want to delete this contact?')\">Delete</a></td></tr>\n";
		}
		?>
		</tbody>
	</table>
    <script>
        $(document).ready(function() {
			new DataTable('#personnel', {
				scrollX: true
			});
        });
    </script>
</body>
</html>