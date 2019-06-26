<?php include("head-r.php") ?>
<?php include("cred.php") ?>
<?php
	$today = date('Y-m-d');
	$yesterday = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	$twodaysago = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-2, date("Y")));
	$threedaysago = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
	$fourdaysago = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-4, date("Y")));
	$thismonth = date('Y-m-1');
	$lastmonth = date('Y-m-1', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	$twomonthsago = date('Y-m-1', mktime(0, 0, 0, date("m")-2, date("d"), date("Y")));
	$threemonthsago = date('Y-m-1', mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
	$fourmonthsago = date('Y-m-1', mktime(0, 0, 0, date("m")-4, date("d"), date("Y")));
?>

<div class="section">
	<div class="secleft">
		<h2>Re-Ban a date range:</h2>
		Enter start & end dates and click to review.<br /><br />
		<form autocomplete="off" action='reban-date-view.php' method='GET'>
			<table>
				<tr>
				<tr><td>Starting Date: </td><td><input type="text" id="dateFrom" name="dateFrom" /></td></tr>
				<tr><td>Ending Date: </td><td><input type="text" id="dateTo" name="dateTo" /></td></tr>
				<tr><td><input type='submit' name='submit' value='Ban' /></td></tr>
			</table>
		</form>
		<br />Note: Range can be a single day, but start and end dates must both be filled in.<br />
	</div>

	<div class="secright">
		<h2>Re-Ban a recent day:</h2>
		Released IPs over the past five days. Click below to review.<br /><br />
		
<?php
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$today} 00:00:00' AND '{$today} 23:59:59' AND (flag=1 OR flag=2)";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){  
	if ($row['value_occurrence'] == 1){$singular="";}else{$singular="s";}
	echo "<a href=\"./reban-date-view.php?dateFrom=".$today."&dateTo=".$today."&submit=Ban\">".number_format($row['value_occurrence'])." Hit".$singular."</a> Today<br />"; 
	}
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$yesterday} 00:00:00' AND '{$yesterday} 23:59:59' AND (flag=1 OR flag=2)";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ 
	if ($row['value_occurrence'] == 1){$singular="";}else{$singular="s";}
	echo "<a href=\"./reban-date-view.php?dateFrom=".$yesterday."&dateTo=".$yesterday."&submit=Ban\">".number_format($row['value_occurrence'])." Hit".$singular."</a> Yesterday<br />"; 
	}
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$twodaysago} 00:00:00' AND '{$twodaysago} 23:59:59' AND (flag=1 OR flag=2)";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ 
	if ($row['value_occurrence'] == 1){$singular="";}else{$singular="s";}
	echo "<a href=\"./reban-date-view.php?dateFrom=".$twodaysago."&dateTo=".$twodaysago."&submit=Ban\">".number_format($row['value_occurrence'])." Hit".$singular."</a> on ".date("l", strtotime($twodaysago))."<br />"; 
	}
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$threedaysago} 00:00:00' AND '{$threedaysago} 23:59:59' AND (flag=1 OR flag=2)";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ 
	if ($row['value_occurrence'] == 1){$singular="";}else{$singular="s";}
	echo "<a href=\"./reban-date-view.php?dateFrom=".$threedaysago."&dateTo=".$threedaysago."&submit=Ban\">".number_format($row['value_occurrence'])." Hit".$singular."</a> on ".date("l", strtotime($threedaysago))."<br />"; 
	}
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$fourdaysago} 00:00:00' AND '{$fourdaysago} 23:59:59' AND (flag=1 OR flag=2)";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ 
	if ($row['value_occurrence'] == 1){$singular="";}else{$singular="s";}
	echo "<a href=\"./reban-date-view.php?dateFrom=".$fourdaysago."&dateTo=".$fourdaysago."&submit=Ban\">".number_format($row['value_occurrence'])." Hit".$singular."</a> on ".date("l", strtotime($fourdaysago))."<br />"; 
	}

?>
	</div>
	<div class="clear"></div>
</div>

<div class="section">
	<div class="secleft">
		<h2>Re-Ban a Ban Reason:</h2>
		Released IPs for the following ban reasons. Click to review.<br /><br />
<?php
	$sqlcount = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE flag=1 OR flag=2";
	$res_count = mysqli_query($con,$sqlcount);
	$total_rows = mysqli_fetch_array($res_count)[0];
	if ($total_rows > 0) { 
		$sql = "SELECT `ban_reason`, COUNT(`ban_reason`) AS `value_occurrence` FROM `hm_fwban` WHERE flag=1 OR flag=2 GROUP BY `ban_reason` ORDER BY `value_occurrence` DESC";
		$res_data = mysqli_query($con,$sql);
		while($row = mysqli_fetch_array($res_data)){ 
		if ($row['value_occurrence'] == 1){$singular="";}else{$singular="s";}
		echo number_format($row['value_occurrence'])." hit".$singular." for <a href=\"./reban-br-view.php?submit=Reban&ban_reason=".$row['ban_reason']."\">".$row['ban_reason']."</a><br />"; 
		}
	} else {
		echo "No released records for any ban reason.";
	}

?>
	</div>

	<div class="secright">
		<h2>Re-Ban a Country:</h2>
		Will search for matching released IPs.<br /><br />
		<form autocomplete="off" action='reban-country-view.php' method='GET'>
			<input type="text" id="country" name="country">
			<input type='submit' name='submit' value='Ban' />
		</form>
		<br />Note: Only applies to previously released IPs for the selected country.
	</div>
	<div class="clear"></div>
</div>

<div class="section">
	<div class="secleft">
		<h2>Manually ban an IP range:</h2>
		Enter an IP or IP range. Will search for matches and if found will reban. If no matches found will add IP to firewall ban list with reason: "Manual". Not dependent on previous bans/releases.<br /><br />
		<form autocomplete="off" action='reban-iprange.php' method='GET'>
			<input type="text" pattern="^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){2,3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$" title="255.255.255.255 OR 255.255.255" id="ipRange" name="ipRange">
			<input type='submit' name='submit' value='Ban' onclick="return confirm('Are you sure you want to BAN this IP range?')" />
		</form>
		<br />IP Ranges MUST be in: <br />
		<b>255.255.255.255</b> OR <br />
		<b>255.255.255</b> format. <br /><br />
		IP ranges will be automatically converted to CIDR for insertion as firewall rule.
	</div>

	<div class="secright">
		<h2>Ban something else:</h2>
		Whatever shall we ban? Work in progress placeholder. Suggestions welcome.
	</div>
	<div class="clear"></div>
</div>

<?php include("foot.php") ?>