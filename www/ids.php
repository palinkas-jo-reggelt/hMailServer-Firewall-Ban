<?php include("head-gr.php") ?>

<div class="section">
IDS =  Intrusion Detection System. Records all connections to mail server. If mail accepted, IDS entry is deleted. If mail not accepted, IDS entry recorded as Hit+1. When the number of hits = 3, the IP gets added to the firewall ban with ban reason "IDS" and the IDS entry gets deleted. Lists below are not static and subject to change as IDS IPs are deleted after update to firewall ban.  <br /><br />
Click to <a href="./ids-view.php">see all</a> current IDS entries.
</div>

<div class="section">

<?php include("cred.php") ?>
<?php
	$today = date('Y-m-d');
	$yesterday = date('Y-m-d', strtotime(date('Y-m-d')." -1 day"));
	$twodaysago = date('Y-m-d', strtotime(date('Y-m-d')." -2 day"));
	$threedaysago = date('Y-m-d', strtotime(date('Y-m-d')." -3 day"));
	$fourdaysago = date('Y-m-d', strtotime(date('Y-m-d')." -4 day"));
	$thismonth = date('Y-m');
	$lastmonth = date('Y-m', strtotime(date('Y-m')." -1 month"));
	$twomonthsago = date('Y-m', strtotime(date('Y-m')." -2 month"));
	$threemonthsago = date('Y-m', strtotime(date('Y-m')." -3 month"));
	$fourmonthsago = date('Y-m', strtotime(date('Y-m')." -4 month"));

	echo "<div class=\"secleft\">";
	echo "<h2>This Week's IDS Hits:</h2>";

	$sql = "SELECT COUNT(`ipaddress`) AS `IDSips` FROM `hm_ids` WHERE `timestamp` BETWEEN '{$today} 00:00:00' AND '{$today} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./ids-view.php?submit=Search&search=".$today."\">".number_format($row['IDSips'])." IPs hit by IDS</a> Today<br />"; }
	
	$sql = "SELECT COUNT(`ipaddress`) AS `IDSips` FROM `hm_ids` WHERE `timestamp` BETWEEN '{$yesterday} 00:00:00' AND '{$yesterday} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./ids-view.php?submit=Search&search=".$yesterday."\">".number_format($row['IDSips'])." IPs hit by IDS</a> Yesterday<br />"; }
	
	$sql = "SELECT COUNT(`ipaddress`) AS `IDSips` FROM `hm_ids` WHERE `timestamp` BETWEEN '{$twodaysago} 00:00:00' AND '{$twodaysago} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./ids-view.php?submit=Search&search=".$twodaysago."\">".number_format($row['IDSips'])." IPs hit by IDS</a> on ".date("l", strtotime($twodaysago))."<br />"; }
	
	$sql = "SELECT COUNT(`ipaddress`) AS `IDSips` FROM `hm_ids` WHERE `timestamp` BETWEEN '{$threedaysago} 00:00:00' AND '{$threedaysago} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./ids-view.php?submit=Search&search=".$threedaysago."\">".number_format($row['IDSips'])." IPs hit by IDS</a> on ".date("l", strtotime($threedaysago))."<br />"; }
	
	$sql = "SELECT COUNT(`ipaddress`) AS `IDSips` FROM `hm_ids` WHERE `timestamp` BETWEEN '{$fourdaysago} 00:00:00' AND '{$fourdaysago} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./ids-view.php?submit=Search&search=".$fourdaysago."\">".number_format($row['IDSips'])." IPs hit by IDS</a> on ".date("l", strtotime($fourdaysago))."<br />"; }

	echo "<br />";
	echo "</div>";

	echo "<div class=\"secright\">";

	echo "<h2>This Year's Monthly IDS Hits:</h2>";

	$sql = "SELECT COUNT(`ipaddress`) AS `IDSips` FROM `hm_ids` WHERE `timestamp` BETWEEN '{$thismonth}-01 00:00:00' AND NOW()";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./ids-view.php?submit=Search&search=".$thismonth."\">".number_format($row['IDSips'])." IPs hit by IDS</a> in ".date("F", strtotime($thismonth))."<br />"; }
	
	$sql = "SELECT COUNT(`ipaddress`) AS `IDSips` FROM `hm_ids` WHERE `timestamp` BETWEEN '{$lastmonth}-01 00:00:00' AND '{$thismonth}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./ids-view.php?submit=Search&search=".$lastmonth."\">".number_format($row['IDSips'])." IPs hit by IDS</a> in ".date("F", strtotime($lastmonth))."<br />"; }
	
	$sql = "SELECT COUNT(`ipaddress`) AS `IDSips` FROM `hm_ids` WHERE `timestamp` BETWEEN '{$twomonthsago}-01 00:00:00' AND '{$lastmonth}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./ids-view.php?submit=Search&search=".$twomonthsago."\">".number_format($row['IDSips'])." IPs hit by IDS</a> in ".date("F", strtotime($twomonthsago))."<br />"; }

	$sql = "SELECT COUNT(`ipaddress`) AS `IDSips` FROM `hm_ids` WHERE `timestamp` BETWEEN '{$threemonthsago}-01 00:00:00' AND '{$twomonthsago}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./ids-view.php?submit=Search&search=".$threemonthsago."\">".number_format($row['IDSips'])." IPs hit by IDS</a> in ".date("F", strtotime($threemonthsago))."<br />"; }
	
	$sql = "SELECT COUNT(`ipaddress`) AS `IDSips` FROM `hm_ids` WHERE `timestamp` BETWEEN '{$fourmonthsago}-01 00:00:00' AND '{$threemonthsago}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./ids-view.php?submit=Search&search=".$fourmonthsago."\">".number_format($row['IDSips'])." IPs hit by IDS</a> in ".date("F", strtotime($fourmonthsago))."<br />"; }

	echo "<br />";
	echo "</div><div class=\"clear\"></div>";

?>
</div>
<?php include("foot.php") ?>