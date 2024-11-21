<?php
// Include the database connection file
include_once("config.php");

// Fetch operator list
$opertor_list = $mysqli->query("
    SELECT 
        o.name AS OperatorName, 
        o.status AS OperatorStatus, 
        o.email AS OperatorEmail, 
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

function obfuscateEmail($email) {
    $parts = explode('@', $email);
    if (count($parts) === 2) {
        $localPart = substr($parts[0], 0, 2) . str_repeat('*', max(0, strlen($parts[0]) - 2));
        $domain = $parts[1];
        return $localPart . '@' . $domain;
    }
    return $email; // Return unmodified if not a valid email format
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
				<td>Email</td>
				<td>Certification</td>
				<td>User</td>
			</tr>
		</thead>
		<tbody>
		<?php
		while($res = mysqli_fetch_array($opertor_list)) {
			$email = explode('@', $res['OperatorEmail']);
            $user = $email[0];
            $domain = $email[1];
			echo "<tr>";
			echo "<td>".$res['OperatorName']."</td>";
			echo "<td>".$res['OperatorStatus']."</td>";
			// Apply the obfuscateEmail function for visible text, full email in the mailto link
			// $obfuscatedEmail = obfuscateEmail($res['OperatorEmail']);
			// echo "<td><a href=\"mailto:".$res['OperatorEmail']."\">".$obfuscatedEmail."</a></td>";
			echo "<td>
					<script>
						let user = '" . addslashes($user) . "';
						let site = '" . addslashes($domain) . "';
						document.write('<a href=\"mailto:' + user + '@' + site + '\">' + user + '@' + site + '</a>');
					</script>
				</td>\n";
			echo "<td>".$res['HighestCertification']."</td>\n";			
			echo "<td><a href=\"edit.php?id=$res[id]\">Edit</a> </td></tr>\n";
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
