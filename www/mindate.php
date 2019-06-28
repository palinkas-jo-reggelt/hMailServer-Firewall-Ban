<?php include("cred.php") ?>
<?php
	$query = "SELECT DATE(timestamp) Date FROM hm_fwban ORDER BY DATE(timestamp) ASC LIMIT 1";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
		echo "'".$row['Date']."',";
	}
?>