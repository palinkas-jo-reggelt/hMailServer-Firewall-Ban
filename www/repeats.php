<?php include("head-gr.php") ?>

<div class="section">
	<div class="secleft">
		<h2>IPs blocked per day from inception:</h2>
		<div id="chart_totalblocksperday_staticdata"></div>
	</div>
	<div class="secright">
		<h2>Average blocks per hour from inception:</h2>
		<div id="chart_blocksperhour_staticdata"></div>
	</div>
	<div class="clear"></div>
</div>


<div class="section">
	<div class="secleft">
		<h2>This Week's Daily Blocks:</h2>

<?php
	include_once("config.php");
	include_once("functions.php");
	include_once("blocksdata.php");

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
			COUNT(*) AS totalblocks 
		FROM (
			SELECT * 
			FROM hm_fwban_rh 
			WHERE '{$today} 00:00:00' <= timestamp
		) AS A 
		WHERE timestamp <= '{$today} 23:59:50'
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "<a href=\"./repeats-view.php?ipdate=Date&search=".$today."\">".number_format($row['ipsblocked'])." IPs blocked</a> Today attemtpting access ".number_format($row['totalblocks'])." times<br />"; 
	}
	echo $dailyblocks;
?>
	<br />
	</div>

	<div class="secright">
		<h2>This Year's Monthly Blocks:</h2>

<?php
	include_once("config.php");
	include_once("functions.php");
	include_once("blocksdata.php");

	$sql = $pdo->prepare("
		SELECT 
			COUNT(DISTINCT(ipaddress)) AS ipsblocked, 
			COUNT(*) AS totalblocks 
		FROM (
			SELECT * 
			FROM hm_fwban_rh 
			WHERE '{$thismonth}-01 00:00:00' <= timestamp
		) AS A 
		WHERE timestamp <= ".DBGetCurrentDateTime()
	);
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		echo "<a href=\"./repeats-view.php?submit=Search&search=".$thismonth."\">".number_format($row['ipsblocked'])." IPs blocked</a> in ".date("F", strtotime($thismonth))." attemtpting access ".number_format($row['totalblocks'])." times<br />"; 
	}

	echo $monthlyblocks;
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
		<br>
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
		<br>
	</div>
	<div class="clear"></div>
</div>
	

<div class="section">
	<div class="secleft">
		<h2>Mark an IP / IP Range Safe:</h2>
		Permanently release an IP range and mark it safe from future bans.<br><br>
		<form autocomplete='off' action='./safe-mark.php' method='GET'> 
			<input type="text" pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\/(2[2-9]|3[0-2]))?$" title="255.255.255.255 OR 255.255.255.255/23" id="ipRange" name="ipRange">
			<input type='submit' name='submit' value='SafeIP' >
		</form>
		<br>IP Ranges MUST be in: <br>
		<b>255.255.255.255</b> OR <br>
		<b>255.255.255.255/24</b> format. <br><br>
		Single IPs will be automatically converted to /32 CIDR for search purposes. Netmask /22 - /32 only.<br>
	</div>

	<div class="secright">
		<h2>Disable IP Safe Status:</h2>
		Remove safe status from an IP and reban.<br><br>
		<form autocomplete='off' action='./safe-unmark.php' method='GET'> 
			<input type="text" pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\/(2[2-9]|3[0-2]))?$" title="255.255.255.255 OR 255.255.255.255/23" id="ipRange" name="ipRange">
			<input type='submit' name='submit' value='UnSafeIP' >
		</form>
		<br>IP Ranges MUST be in: <br>
		<b>255.255.255.255</b> OR <br>
		<b>255.255.255.255/24</b> format. <br><br>
		Single IPs will be automatically converted to /32 CIDR for search purposes. Netmask /22 - /32 only.<br>
	</div>
	<div class="clear"></div>
</div>


<div class="section">
	<div class="secleft">
		<h2>Blocks Analyzer</h2>
		See how many IPs have returned for a given number of days.<br><br>
		<a href="./blocks.php">Blocks Analyzer</a>
	</div>

	<div class="secright">
	</div>
	<div class="clear"></div>
</div>


</div>
<?php include("foot.php") ?>