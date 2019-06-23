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
	if (isset($_GET['ban_reason'])) {
	$ban_reason = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['ban_reason'])));
	} else {
		$ban_reason = "";
	}

	if (empty($ban_reason)){echo "Error: No IP entries for ban reason ".$ban_reason."<br /><br />";} else {

		$sqlcount = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `ban_reason` LIKE '{$ban_reason}' AND (flag IS NULL OR flag=3)";
		$res_count = mysqli_query($con,$sqlcount);
		$total_rows = mysqli_fetch_array($res_count)[0];
		if ($total_rows > 0) { 
			echo "<br />".number_format($total_rows)." hits for <a href=\"search.php?submit=Search&search=".$ban_reason."\">".$ban_reason."</a> have been released from the firewall.<br />";
			$sql = "SELECT `id` FROM `hm_fwban` WHERE `ban_reason` LIKE '{$ban_reason}' AND (flag IS NULL OR flag=3)";
			$res_data = mysqli_query($con,$sql);
			while($row = mysqli_fetch_array($res_data)){
				$sql = "UPDATE hm_fwban SET flag=2 WHERE id=".$row['id'];
				$result = mysqli_query($con,$sql);
				if(!$result){ die('Could not update data: ' . mysqli_error()); }
			}
		} else {
			echo "<br />Error: No unreleased records for \"<b>".$ban_reason."</b>\". Please <a href=\"search.php?submit=Search&search=".$ban_reason."\">search release status</a> or check the spelling and try again.";
		}
	}
?>
</div>

<?php include("foot.php") ?>