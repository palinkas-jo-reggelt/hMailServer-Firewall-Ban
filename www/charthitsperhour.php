<?php include("cred.php") ?>
<?php 
	$query = "SELECT DATE_FORMAT(timestamp, '%H') AS hourly, COUNT(id) AS ipperday FROM hm_fwban GROUP BY hourly";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
		echo "[[".$row['hourly'].", 0, 0], ".$row['ipperday']."],";
	}
?> 
