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
	if (isset($_GET['submit'])){$button = $_GET['submit'];}else{$button = "";}
	if (isset($_GET['country'])){$country=mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['country'])));}else{$country="";}

	if (empty($country)){echo "Error: No country selected. Please see administrator.<br /><br />";} 
	else {
		$sqlcount = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `country` LIKE '%{$country}%' AND (flag=1 OR flag=2)";
		$res_count = mysqli_query($con,$sqlcount);
		$total_rows = mysqli_fetch_array($res_count)[0];
		if ($total_rows > 0) { 
			echo "<br />".number_format($total_rows)." hits for <a href=\"search.php?submit=Search&search=".$country."&RS=NO\">".$country."</a> have been re-banned the firewall.<br />";
			$sql = "SELECT `id` FROM `hm_fwban` WHERE `country` LIKE '%{$country}%' AND (flag=1 OR flag=2)";
			$res_data = mysqli_query($con,$sql);
			while($row = mysqli_fetch_array($res_data)){
				$sql = "UPDATE hm_fwban SET flag=3 WHERE id=".$row['id'];
				$result = mysqli_query($con,$sql);
				if(!$result){ die('Could not update data: ' . mysqli_error()); }
			}
		} else {
			echo "<br />Error: No released records for \"<b>".$country."</b>\". Please <a href=\"search.php?submit=Search&search=".$country."\">search release status</a> or check the spelling and try again.";
		}
	}
	mysqli_close($con);
?>
</div>

<?php include("foot.php") ?>