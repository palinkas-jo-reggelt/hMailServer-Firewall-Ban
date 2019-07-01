<?php include("cred.php") ?>
<?php 
	$query = "SELECT DATE_FORMAT(timestamp, '%Y, %c, %e') AS daily, DATE_FORMAT(timestamp, '%Y') AS year, (DATE_FORMAT(timestamp, '%c') - 1) AS month, DATE_FORMAT(timestamp, '%e') AS day, COUNT(id) AS ipperday FROM hm_fwban WHERE DATE(timestamp) < DATE(NOW()) GROUP BY daily ASC";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
		echo "[new Date(".$row['year'].", ".$row['month'].", ".$row['day']."), ".$row['ipperday']."],";
	}
?>