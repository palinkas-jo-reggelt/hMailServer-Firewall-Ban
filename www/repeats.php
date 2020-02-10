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
	<div class="secleft">
	<h2>This Week's Daily Blocked IPs:</h2>

<?php
	include_once("config.php");
	include_once("functions.php");

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

	$sql = $pdo->prepare("
		SELECT 
			COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
			COUNT(ipaddress) AS totalblocks 
		FROM hm_fwban_rh 
		WHERE timestamp BETWEEN '{$today} 00:00:00' AND '{$today} 23:59:59'
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "<a href=\"./repeats-view.php?submit=Search&search=".$today."\">".number_format($row['ipsblocked'])." IPs blocked</a> Today attemtpting access ".number_format($row['totalblocks'])." times<br />"; 
	}
	
	$sql = $pdo->prepare("
		SELECT 
			COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
			COUNT(ipaddress) AS totalblocks 
		FROM hm_fwban_rh 
		WHERE timestamp BETWEEN '{$yesterday} 00:00:00' AND '{$yesterday} 23:59:59'
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "<a href=\"./repeats-view.php?submit=Search&search=".$yesterday."\">".number_format($row['ipsblocked'])." IPs blocked</a> Yesterday attemtpting access ".number_format($row['totalblocks'])." times<br />"; 
	}
	
	$sql = $pdo->prepare("
		SELECT 
			COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
			COUNT(ipaddress) AS totalblocks 
		FROM hm_fwban_rh 
		WHERE timestamp BETWEEN '{$twodaysago} 00:00:00' AND '{$twodaysago} 23:59:59'
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "<a href=\"./repeats-view.php?submit=Search&search=".$twodaysago."\">".number_format($row['ipsblocked'])." IPs blocked</a> on ".date("l", strtotime($twodaysago))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; 
	}
	
	$sql = $pdo->prepare("
		SELECT 
			COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
			COUNT(ipaddress) AS totalblocks 
		FROM hm_fwban_rh 
		WHERE timestamp BETWEEN '{$threedaysago} 00:00:00' AND '{$threedaysago} 23:59:59'
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "<a href=\"./repeats-view.php?submit=Search&search=".$threedaysago."\">".number_format($row['ipsblocked'])." IPs blocked</a> on ".date("l", strtotime($threedaysago))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; 
	}
	
	$sql = $pdo->prepare("
		SELECT 
			COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
			COUNT(ipaddress) AS totalblocks 
		FROM hm_fwban_rh 
		WHERE timestamp BETWEEN '{$fourdaysago} 00:00:00' AND '{$fourdaysago} 23:59:59'
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "<a href=\"./repeats-view.php?submit=Search&search=".$fourdaysago."\">".number_format($row['ipsblocked'])." IPs blocked</a> on ".date("l", strtotime($fourdaysago))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; 
	}
?>
	<br />
	</div>

	<div class="secright">
		<h2>This Year's Monthly Blocks:</h2>

<?php
	include_once("config.php");
	include_once("functions.php");

	$sql = $pdo->prepare("
		SELECT 
			COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
			COUNT(ipaddress) AS totalblocks 
		FROM hm_fwban_rh 
		WHERE timestamp BETWEEN '{$thismonth}-01 00:00:00' AND NOW()
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "<a href=\"./repeats-view.php?submit=Search&search=".$thismonth."\">".number_format($row['ipsblocked'])." IPs blocked</a> in ".date("F", strtotime($thismonth))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; 
	}
	
	$sql = $pdo->prepare("
		SELECT 
			COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
			COUNT(ipaddress) AS totalblocks 
		FROM hm_fwban_rh 
		WHERE timestamp BETWEEN '{$lastmonth}-01 00:00:00' AND '{$thismonth}-01 00:00:00'
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "<a href=\"./repeats-view.php?submit=Search&search=".$lastmonth."\">".number_format($row['ipsblocked'])." IPs blocked</a> in ".date("F", strtotime($lastmonth))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; 
	}
	
	$sql = $pdo->prepare("
		SELECT 
			COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
			COUNT(ipaddress) AS totalblocks 
		FROM hm_fwban_rh 
		WHERE timestamp BETWEEN '{$twomonthsago}-01 00:00:00' AND '{$lastmonth}-01 00:00:00'
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "<a href=\"./repeats-view.php?submit=Search&search=".$twomonthsago."\">".number_format($row['ipsblocked'])." IPs blocked</a> in ".date("F", strtotime($twomonthsago))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; 
	}

	$sql = $pdo->prepare("
		SELECT 
			COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
			COUNT(ipaddress) AS totalblocks 
		FROM hm_fwban_rh 
		WHERE timestamp BETWEEN '{$threemonthsago}-01 00:00:00' AND '{$twomonthsago}-01 00:00:00'
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "<a href=\"./repeats-view.php?submit=Search&search=".$threemonthsago."\">".number_format($row['ipsblocked'])." IPs blocked</a> in ".date("F", strtotime($threemonthsago))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; 
	}
	
	$sql = $pdo->prepare("
		SELECT 
			COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
			COUNT(ipaddress) AS totalblocks 
		FROM hm_fwban_rh 
		WHERE timestamp BETWEEN '{$fourmonthsago}-01 00:00:00' AND '{$threemonthsago}-01 00:00:00'
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "<a href=\"./repeats-view.php?submit=Search&search=".$fourmonthsago."\">".number_format($row['ipsblocked'])." IPs blocked</a> in ".date("F", strtotime($fourmonthsago))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; 
	}
?>
	<br />
	</div>
	<div class="clear"></div>
</div>


<div class="section">
	<div class="secleft">
		<h2>Search for Repeat Blocks by IP:</h2>
		<form autocomplete='off' action='repeats-view.php' method='GET'> 
			<input type='text' size='20' name='search' pattern='^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){1,3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$' title='255.255.255.255 OR 255.255.255 OR 255.255' placeholder='255.255.255.255...'>
			<input type='submit' name='submit' value='Search-IP' >
		</form>
		<br />
	</div>

	<div class="secright">
		<h2>Search for Repeat Blocks by Date Range:</h2>
		<form autocomplete='off' action='repeats-date.php' method='GET'>
			<table>
				<tr><td>Starting Date: </td><td><input type='text' id='dateFrom' name='dateFrom' placeholder='Starting Date...' /></td></tr>
				<tr><td>Ending Date: </td><td><input type='text' id='dateTo' name='dateTo' placeholder='Ending Date...' /></td></tr>
				<tr><td><input type='submit' name='submit' value='Search' /></td></tr>
			</table>
		</form>
		<br />
	</div>
	<div class="clear"></div>
</div>
	

<div class="section">
	<div class="secleft">
		<h2>Mark an IP / IP Range Safe:</h2>
		Permanently release an IP range and mark it safe from future bans.<br /><br />
		<form autocomplete='off' action='./safe-mark.php' method='GET'> 
			<input type="text" pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\/(2[2-9]|3[0-2]))?$" title="255.255.255.255 OR 255.255.255.255/23" id="ipRange" name="ipRange">
			<input type='submit' name='submit' value='SafeIP' >
		</form>
		<br />IP Ranges MUST be in: <br />
		<b>255.255.255.255</b> OR <br />
		<b>255.255.255.255/24</b> format. <br /><br />
		Single IPs will be automatically converted to /32 CIDR for search purposes. Netmask /22 - /32 only.<br />
	</div>

	<div class="secright">
		<h2>Disable IP Safe Status:</h2>
		Remove safe status from an IP and reban.<br /><br />
		<form autocomplete='off' action='./safe-unmark.php' method='GET'> 
			<input type="text" pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\/(2[2-9]|3[0-2]))?$" title="255.255.255.255 OR 255.255.255.255/23" id="ipRange" name="ipRange">
			<input type='submit' name='submit' value='UnSafeIP' >
		</form>
		<br />IP Ranges MUST be in: <br />
		<b>255.255.255.255</b> OR <br />
		<b>255.255.255.255/24</b> format. <br /><br />
		Single IPs will be automatically converted to /32 CIDR for search purposes. Netmask /22 - /32 only.<br />
	</div>
	<div class="clear"></div>
</div>


<div class="section">
	<div class="secleft">
		<h2>Blocks Analyzer</h2>
		See how many IPs have returned for a given number of days.<br /><br />
		<a href="./blocks.php">Blocks Analyzer</a>
	</div>

	<div class="secright">
	</div>
	<div class="clear"></div>
</div>


</div>
<?php include("foot.php") ?>