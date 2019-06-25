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
	if (isset($_GET['submit'])) {$button = $_GET ['submit'];} else {$button = "";}
	if (isset($_GET['id'])) {$id = (mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['id']))));} else {$id = "";}

	if (empty($id)){ echo "Error: No IP range specified\"<br /><br />"; } 
	else {
		$sql = "SELECT `id`, `ipaddress` FROM `hm_fwban` WHERE `id`='{$id}'";
		$res_data = mysqli_query($con,$sql);
		while($row = mysqli_fetch_array($res_data)){
			$sql = "UPDATE hm_fwban SET flag=3 WHERE id=".$row['id'];
			$result = mysqli_query($con,$sql);
			if(!$result){ die('Could not update data: ' . mysqli_error()); }
			echo "<a href=\"search.php?submit=Search&search=".$row['ipaddress']."\">".$row['ipaddress']."</a> with ID number ".$row['id']." has been marked for reban to the firewall.<br />";
		}
	}
	mysqli_close($con);
?>
</div>

<?php include("foot.php") ?>