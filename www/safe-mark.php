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
	if (isset($_GET['submit'])){$button = $_GET ['submit'];} else {$button = "";}
	if (isset($_GET['ipRange'])) {
		if(preg_match("/^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/", ($_GET['ipRange']))) {
			$ipRange = (mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['ipRange']))));
		} else {
			$ipRange = "";
		}
	} else {
		$ipRange = "";
	}

	if (empty($ipRange)){echo "<br /><br />Error: IP range empty. Please see administrator.<br /><br />";}
	else {
		$sqlcount = "SELECT COUNT(DISTINCT(`ipaddress`)) AS `value_occurrence` FROM `hm_fwban` WHERE `ipaddress` = '{$ipRange}'";
		$res_count = mysqli_query($con,$sqlcount);
		$total_rows = mysqli_fetch_array($res_count)[0];
		if ($total_rows > 0) { 
			echo "<br />IP range <a href=\"./search.php?submit=Search&search=".$ipRange."\">\"<b>".$ipRange."</b>\"</a> has been permanently released and marked safe from future firewall bans.<br />";
			$sql = "SELECT `id` FROM `hm_fwban` WHERE `ipaddress` LIKE '{$ipRange}%'";
			$res_data = mysqli_query($con,$sql);
			while($row = mysqli_fetch_array($res_data)){
				$sql = "UPDATE hm_fwban SET flag=5 WHERE id=".$row['id'];
				$result = mysqli_query($con,$sql);
				if(!$result){ die('Could not update data: ' . mysqli_error()); }
			}
		} else {
			echo "<br /><br />Error: IP range \"<b>".$ipRange."</b>\" could not be found in database. Please try again.";
		}
	}
	mysqli_close($con);
?>
</div>

<?php include("foot.php") ?>