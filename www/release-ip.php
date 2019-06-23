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
	if (isset($_GET['search'])) {
	$search = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['search'])));
	} else {
		$search = "";
	}
  
	$sql = "UPDATE hm_fwban SET flag=2 WHERE id='{$search}'";
	$res_data = mysqli_query($con,$sql);
	if(!$res_data)
		{
		die('Could not update data: ' . mysqli_error());
		}

	$sql = "SELECT id, ipaddress FROM hm_fwban WHERE id='{$search}'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "IP address ".$row['ipaddress']." with ID number ".$row['id']." was successfully released from firewall ban."; }

	mysqli_close($con);
?>
</div>

<?php include("foot.php") ?>