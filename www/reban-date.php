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
	if (isset($_GET['dateFrom'])) {
	$dateFrom = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['dateFrom'])));
	} else {
		$dateFrom = "";
	}
	if (isset($_GET['dateTo'])) {
	$dateTo = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['dateTo'])));
	} else {
		$dateFrom = "";
	}

	if (empty($dateFrom)){
		echo "You did not put in a beginning date. Both beginning and ending dates are required for date range release even if the range is a single day.<br /><br />";
	} elseif (empty($dateTo)){
			echo "You did not put in an ending date. Both beginning and ending dates are required for date range release even if the range is a single day.<br /><br />";
	} else {
		
		$sqlcount = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTo} 23:59:59' AND (flag=1 OR flag=2)";
		$res_count = mysqli_query($con,$sqlcount);
		$total_rows = mysqli_fetch_array($res_count)[0];
		if ($total_rows > 0) { 
			echo "<br />".number_format($total_rows)." hits for date range <a href=\"search-date.php?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&RS=NO\">\"<b>".$dateFrom."</b>\" to \"<b>".$dateTo."</b>\"</a> have been marked for re-BAN to the firewall.<br />";
			$sql = "SELECT `id` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTo} 23:59:59' AND (flag=1 OR flag=2)";
			$res_data = mysqli_query($con,$sql);
			while($row = mysqli_fetch_array($res_data)){
				$sql = "UPDATE hm_fwban SET flag=3 WHERE id=".$row['id'];
				$result = mysqli_query($con,$sql);
				if(!$result){ die('Could not update data: ' . mysqli_error()); }
			}
		} else {
			echo "<br />Error: Date range \"<b>".$dateFrom."</b>\" to \"<b>".$dateTo."</b>\" contains no entries in database that were previously released. Please try again.";
		}
	}
	mysqli_close($con);
?>
</div>

<?php include("foot.php") ?>