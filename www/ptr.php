<?php include("cred.php") ?>

<html>
<head>
<title>hMailServer Firewall Ban</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Style-Type" content="text/css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" media="all" href="stylesheet.css">
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Oswald" rel="stylesheet"> 
</head>
<body onBlur="window.focus()">

<div class="wrapper">
<div class="section">

<?php
	if (isset($_GET['ip'])) {$ip = $_GET ['ip'];} else {$ip = "";}

	// echo "IP: ".$ip."<br />";
	// $ptr = gethostbyaddr($ip);
	// if ($ptr == $ip){
		// echo "PTR: No PTR <br />";
	// } else {
		// echo "PTR: ".$ptr."<br />";
	// }	

	$sql = "SELECT DATE(timestamp) AS dateptr, ban_reason, helo, ptr FROM hm_fwban WHERE ipaddress = '$ip'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){
		echo "IP: ".$ip."<br />";
		echo "PTR: ".$row['ptr']."<br />";
		echo "HELO: ".$row['helo']."<br />";
		echo "Ban Reason: ".$row['ban_reason']."<br />";
		echo "Ban Date: ".$row['dateptr']."<br />";
	}
?>

</div>
</div>

</body>
</html>