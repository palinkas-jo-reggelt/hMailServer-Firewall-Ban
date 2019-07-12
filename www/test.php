<?php include("cred.php") ?>
<?php 
	$query = "SELECT DATE(timestamp) AS daily, DATE_FORMAT(timestamp, '%Y') AS year, (DATE_FORMAT(timestamp, '%c') - 1) AS month, DATE_FORMAT(timestamp, '%e') AS day, COUNT(id) AS ipperday FROM hm_fwban WHERE DATE(timestamp) < DATE(NOW()) GROUP BY daily ASC";
	$exec = mysqli_query($con,$query);
	while($row = mysqli_fetch_array($exec)){
		$res_knocks = mysqli_query($con,"SELECT COUNT(ipaddress) FROM hm_fwban_rh WHERE DATE(timestamp)='".$row['daily']."'");
		$knocks = mysqli_fetch_array($res_knocks)[0];
		echo "[new Date(".$row['year'].", ".$row['month'].", ".$row['day']."), ".$row['ipperday'].", ".$knocks."],";
	}
?>
