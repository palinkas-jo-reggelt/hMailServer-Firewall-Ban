<?php include("head-gr.php") ?>

<div class="section">
	<div class="secleft">
		<h2>IPs blocked per day from inception:</h2>
		<div id="chart_blocksperday"></div>
	</div>
	<div class="secright">
		<h2>Average blocks per hour from inception:</h2>
		<div id="chart_blocksperhour"></div>
	</div>
	<div class="clear"></div>
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
	echo "<h2>This Week's Daily Blocked IPs:</h2>";

	$sql = "SELECT COUNT(DISTINCT(`ipaddress`)) AS `ipsblocked`, COUNT(`ipaddress`) AS `totalblocks` FROM `hm_fwban_rh` WHERE `timestamp` BETWEEN '{$today} 00:00:00' AND '{$today} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./repeats-view.php?submit=Search&search=".$today."\">".number_format($row['ipsblocked'])." IPs blocked</a> Today attemtpting access ".number_format($row['totalblocks'])." times<br />"; }
	
	$sql = "SELECT COUNT(DISTINCT(`ipaddress`)) AS `ipsblocked`, COUNT(`ipaddress`) AS `totalblocks` FROM `hm_fwban_rh` WHERE `timestamp` BETWEEN '{$yesterday} 00:00:00' AND '{$yesterday} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./repeats-view.php?submit=Search&search=".$yesterday."\">".number_format($row['ipsblocked'])." IPs blocked</a> Yesterday attemtpting access ".number_format($row['totalblocks'])." times<br />"; }
	
	$sql = "SELECT COUNT(DISTINCT(`ipaddress`)) AS `ipsblocked`, COUNT(`ipaddress`) AS `totalblocks` FROM `hm_fwban_rh` WHERE `timestamp` BETWEEN '{$twodaysago} 00:00:00' AND '{$twodaysago} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./repeats-view.php?submit=Search&search=".$twodaysago."\">".number_format($row['ipsblocked'])." IPs blocked</a> on ".date("l", strtotime($twodaysago))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; }
	
	$sql = "SELECT COUNT(DISTINCT(`ipaddress`)) AS `ipsblocked`, COUNT(`ipaddress`) AS `totalblocks` FROM `hm_fwban_rh` WHERE `timestamp` BETWEEN '{$threedaysago} 00:00:00' AND '{$threedaysago} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./repeats-view.php?submit=Search&search=".$threedaysago."\">".number_format($row['ipsblocked'])." IPs blocked</a> on ".date("l", strtotime($threedaysago))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; }
	
	$sql = "SELECT COUNT(DISTINCT(`ipaddress`)) AS `ipsblocked`, COUNT(`ipaddress`) AS `totalblocks` FROM `hm_fwban_rh` WHERE `timestamp` BETWEEN '{$fourdaysago} 00:00:00' AND '{$fourdaysago} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./repeats-view.php?submit=Search&search=".$fourdaysago."\">".number_format($row['ipsblocked'])." IPs blocked</a> on ".date("l", strtotime($fourdaysago))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; }

	echo "<br />";
	echo "</div>";

	echo "<div class=\"secright\">";

	echo "<h2>This Year's Monthly Blocks:</h2>";

	$sql = "SELECT COUNT(DISTINCT(`ipaddress`)) AS `ipsblocked`, COUNT(`ipaddress`) AS `totalblocks` FROM `hm_fwban_rh` WHERE `timestamp` BETWEEN '{$thismonth}-01 00:00:00' AND NOW()";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./repeats-view.php?submit=Search&search=".$thismonth."\">".number_format($row['ipsblocked'])." IPs blocked</a> in ".date("F", strtotime($thismonth))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; }
	
	$sql = "SELECT COUNT(DISTINCT(`ipaddress`)) AS `ipsblocked`, COUNT(`ipaddress`) AS `totalblocks` FROM `hm_fwban_rh` WHERE `timestamp` BETWEEN '{$lastmonth}-01 00:00:00' AND '{$thismonth}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./repeats-view.php?submit=Search&search=".$lastmonth."\">".number_format($row['ipsblocked'])." IPs blocked</a> in ".date("F", strtotime($lastmonth))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; }
	
	$sql = "SELECT COUNT(DISTINCT(`ipaddress`)) AS `ipsblocked`, COUNT(`ipaddress`) AS `totalblocks` FROM `hm_fwban_rh` WHERE `timestamp` BETWEEN '{$twomonthsago}-01 00:00:00' AND '{$lastmonth}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./repeats-view.php?submit=Search&search=".$twomonthsago."\">".number_format($row['ipsblocked'])." IPs blocked</a> in ".date("F", strtotime($twomonthsago))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; }

	$sql = "SELECT COUNT(DISTINCT(`ipaddress`)) AS `ipsblocked`, COUNT(`ipaddress`) AS `totalblocks` FROM `hm_fwban_rh` WHERE `timestamp` BETWEEN '{$threemonthsago}-01 00:00:00' AND '{$twomonthsago}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./repeats-view.php?submit=Search&search=".$threemonthsago."\">".number_format($row['ipsblocked'])." IPs blocked</a> in ".date("F", strtotime($threemonthsago))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; }
	
	$sql = "SELECT COUNT(DISTINCT(`ipaddress`)) AS `ipsblocked`, COUNT(`ipaddress`) AS `totalblocks` FROM `hm_fwban_rh` WHERE `timestamp` BETWEEN '{$fourmonthsago}-01 00:00:00' AND '{$threemonthsago}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./repeats-view.php?submit=Search&search=".$fourmonthsago."\">".number_format($row['ipsblocked'])." IPs blocked</a> in ".date("F", strtotime($fourmonthsago))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; }

	echo "<br />";
	echo "</div><div class=\"clear\"></div>";


	echo "<div class=\"secleft\">";
	echo "<h2>Search for Repeat Blocks by IP:</h2>";
	echo "<form autocomplete='off' action='repeats-view.php' method='GET'> ";
	echo	"<input type='text' size='20' name='search' pattern='^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){1,3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$' title='255.255.255.255 OR 255.255.255 OR 255.255' placeholder='255.255.255.255...'>";
	echo	" ";
	echo	"<input type='submit' name='submit' value='Search-IP' >";
	echo "</form>";
	echo "<br />";
	echo "</div>";

	echo "<div class=\"secright\">";
	echo "<h2>Search for Repeat Blocks by Date Range:</h2>";
	echo "<form autocomplete='off' action='repeats-date.php' method='GET'>";
	echo "<table>";
	echo "<tr><td>Starting Date: </td><td><input type='text' id='dateFrom' name='dateFrom' placeholder='Starting Date...' /></td></tr>";
	echo "<tr><td>Ending Date: </td><td><input type='text' id='dateTo' name='dateTo' placeholder='Ending Date...' /></td></tr>";
	echo "<tr><td><input type='submit' name='submit' value='Search' /></td></tr>";
	echo "</table>";
	echo "</form>";
	echo "<br />";
	echo "</div><div class=\"clear\"></div>";
	

	echo "<div class=\"secleft\">";
	echo "<h2>Mark an IP Safe:</h2>";
	echo "Permanently release an IP and mark it safe from future bans.<br /><br />";
	echo "<form autocomplete='off' action='./safe-mark.php' method='GET'> ";
	echo	"<input type='text' size='20' name='ipRange' pattern='^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$' title='255.255.255.255' placeholder='255.255.255.255...'>";
	echo	" ";
	echo	"<input type='submit' name='submit' value='SafeIP' >";
	echo "</form>";
	echo "<br />";
	echo "</div>";

	echo "<div class=\"secright\">";
	echo "<h2>Disable IP Safe Status:</h2>";
	echo "Remove safe status from an IP and reban.<br /><br />";
	echo "<form autocomplete='off' action='./safe-unmark.php' method='GET'> ";
	echo	"<input type='text' size='20' name='search' pattern='^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$' title='255.255.255.255' placeholder='255.255.255.255...'>";
	echo	" ";
	echo	"<input type='submit' name='submit' value='UnSafeIP' >";
	echo "</form>";
	echo "<br />";
	echo "</div><div class=\"clear\"></div>";


?>
</div>
<?php include("foot.php") ?>