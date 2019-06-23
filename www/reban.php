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
		<form autocomplete="off" action='reban-date.php' method='GET'>
			<table>
				<tr>
				<tr><td>Starting Date: </td><td><input type="text" id="dateFrom" name="dateFrom" /></td></tr>
				<tr><td>Ending Date: </td><td><input type="text" id="dateTo" name="dateTo" /></td></tr>
				<tr><td><input type='submit' name='submit' value='Ban' onclick="return confirm('Are you sure you want to BAN this date range?')" /></td></tr>
			</table>
		</form>
		Note: Range can be a single day, but start and end dates must both be filled in.
	</div>

	<div class="secright">
		<h2>Re-Ban a recent day:</h2>
		
<?php
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban_demo` WHERE `timestamp` BETWEEN '{$today} 00:00:00' AND '{$today} 23:59:59' AND (flag=1 OR flag=2)";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./reban-date.php?dateFrom=".$today."&dateTo=".$today."&submit=Ban\" OnClick=\"return confirm('Are you sure you want to BAN all of today\'s released hits?')\">".number_format($row['value_occurrence'])." Released Hits</a> Today. Click to re-ban.<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban_demo` WHERE `timestamp` BETWEEN '{$yesterday} 00:00:00' AND '{$yesterday} 23:59:59' AND (flag=1 OR flag=2)";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./reban-date.php?dateFrom=".$yesterday."&dateTo=".$yesterday."&submit=Ban\" OnClick=\"return confirm('Are you sure you want to BAN all of yesterday\'s released hits?')\">".number_format($row['value_occurrence'])." Released Hits</a> Yesterday. Click to re-ban.<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban_demo` WHERE `timestamp` BETWEEN '{$twodaysago} 00:00:00' AND '{$twodaysago} 23:59:59' AND (flag=1 OR flag=2)";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./reban-date.php?dateFrom=".$twodaysago."&dateTo=".$twodaysago."&submit=Ban\" onclick=\"return confirm('Are you sure you want to BAN all of ".date("l", strtotime($twodaysago))."\'s released hits?')\">".number_format($row['value_occurrence'])." Released Hits</a> on ".date("l", strtotime($twodaysago)).". Click to re-ban.<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban_demo` WHERE `timestamp` BETWEEN '{$threedaysago} 00:00:00' AND '{$threedaysago} 23:59:59' AND (flag=1 OR flag=2)";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./reban-date.php?dateFrom=".$threedaysago."&dateTo=".$threedaysago."&submit=Ban\" onclick=\"return confirm('Are you sure you want to BAN all of ".date("l", strtotime($threedaysago))."\'s released hits?')\">".number_format($row['value_occurrence'])." Released Hits</a> on ".date("l", strtotime($threedaysago)).". Click to re-ban.<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban_demo` WHERE `timestamp` BETWEEN '{$fourdaysago} 00:00:00' AND '{$fourdaysago} 23:59:59' AND (flag=1 OR flag=2)";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./reban-date.php?dateFrom=".$fourdaysago."&dateTo=".$fourdaysago."&submit=Ban\" onclick=\"return confirm('Are you sure you want to BAN all of ".date("l", strtotime($fourdaysago))."\'s released hits?')\">".number_format($row['value_occurrence'])." Released Hits</a> on ".date("l", strtotime($fourdaysago)).". Click to re-ban.<br />"; }

?>
	</div>
	<div class="clear"></div>
</div>

<div class="section">
	<div class="secleft">
		<h2>Ban an IP range:</h2>
		<form autocomplete="off" action='reban-iprange.php' method='GET'>
			<input type="text" pattern="^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){2,3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$" title="255.255.255.255 OR 255.255.255" id="ipRange" name="ipRange">
			<input type='submit' name='submit' value='Ban' onclick="return confirm('Are you sure you want to BAN this IP range?')" />
		</form>
		<br />IP Ranges MUST be in <br />
		<b>255.255.255.255</b> OR <br />
		<b>255.255.255</b> format. <br />
		IP ranges will be automatically converted to CIDR.
	</div>

	<div class="secright">
		<h2>Ban something else:</h2>
		Whatever shall we ban? Work in progress placeholder. Suggestions welcome.
	</div>
	<div class="clear"></div>
</div>

<div class="section">
	<div class="secleft">
		<h2>Re-Ban a Ban Reason:</h2>
<?php
	$sql = "SELECT `ban_reason`, COUNT(`ban_reason`) AS `value_occurrence` FROM `hm_fwban_demo` WHERE flag=1 OR flag=2 GROUP BY `ban_reason` ORDER BY `value_occurrence` DESC";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo number_format($row['value_occurrence'])." Released hits for <a href=\"./reban-br.php?submit=Release&ban_reason=".$row['ban_reason']."\" OnClick=\"return confirm('Are you sure you want to BAN all of ".$row['ban_reason']."\'s hits?')\">".$row['ban_reason']."</a>. Click to re-ban<br />"; }
?>
	</div>

	<div class="secright">
		<h2>Re-Ban a Country:</h2>
		<form autocomplete="off" action='reban-country.php' method='GET'>
			<input type="text" id="country" name="country">
			<input type='submit' name='submit' value='Ban' onclick="return confirm('Are you sure you want to BAN this country?')" />
		</form>
		Note: Only applies to previously released IPs for your selected country.
	</div>
	<div class="clear"></div>
</div>

<?php include("foot.php") ?>