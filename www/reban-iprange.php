<?php include("head.php") ?>

<div class="section">

<?php include("cred.php") ?>
<?php

	if (isset($_GET['page'])) {
		$page = $_GET['page'];
		$display_pagination = 1;
	} else {
		$page = 1;
		$total_pages = 1;
		$display_pagination = 0;
	}
	if (isset($_GET['submit'])) {
		$button = $_GET ['submit'];
	} else {
		$button = "";
	}
	if (isset($_GET['ipRange'])) {
		if(preg_match("/^((([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){2}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]))$/", ($_GET['ipRange']))) {
			$ipRange = (mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['ipRange'])))).".0/24";
		} else {
			$ipRange = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['ipRange'])));
		}
	} else {
		$ipRange = "";
	}


	if (empty($ipRange)){ echo "Error: No IP range specified\"<br /><br />"; } 
	// elseif (preg_match("/^((([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0]\/[2-4]{2}))$/", ($ipRange))) {
	else {
		echo "<br />IP range <a href=\"search.php?submit=Search&search=".$ipRange."\">".$ipRange."</a> has been marked for ban to the firewall.<br />";
		$sql = "INSERT INTO hm_FWBan (timestamp,ipaddress,ban_reason) VALUES (NOW(),'".$ipRange."','Manual')";
		$result = mysqli_query($con,$sql);
		if(!$result){ die('Could not update data: ' . mysqli_error()); }
	}
	// else {
		// $sqlcount = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `ipaddress` LIKE '{$ipRange}' AND (flag=2 OR flag=1)";
		// $res_count = mysqli_query($con,$sqlcount);
		// $total_rows = mysqli_fetch_array($res_count)[0];
		// if ($total_rows > 0) { 
			// echo "<br />".number_format($total_rows)." hits for IP range <a href=\"search.php?submit=Search&search=".$ipRange."\">".$ipRange."</a> have been marked for reban to the firewall.<br />";
			// $sql = "SELECT `id` FROM `hm_fwban` WHERE `ipaddress` LIKE '{$ipRange}' AND (flag=2 OR flag=1)";
			// $res_data = mysqli_query($con,$sql);
			// while($row = mysqli_fetch_array($res_data)){
				// $sql = "UPDATE hm_fwban SET flag=3 WHERE id=".$row['id'];
				// $result = mysqli_query($con,$sql);
				// if(!$result){ die('Could not update data: ' . mysqli_error()); }
			// }
		// } else {
			// echo "<br />Error: Range \"<b>".$ipRange."</b>\" does not exist in database. Please try again.";
		// }
	// }
	mysqli_close($con);
?>
</div>

<?php include("foot.php") ?>