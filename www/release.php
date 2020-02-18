<?php include("head-r.php") ?>

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
?>

<div class="section">
	<div class="secleft">
		<h2>Release a date range:</h2>
		Enter start & end dates and click to review.<br><br>
		<form autocomplete="off" action='release-date-view.php' method='GET'>
			<table>
				<tr>
				<tr><td>Starting Date: </td><td><input type="text" id="dateFrom" name="dateFrom" /></td></tr>
				<tr><td>Ending Date: </td><td><input type="text" id="dateTo" name="dateTo" /></td></tr>
				<tr><td><input type='submit' name='submit' value='Review' /></td></tr>
			</table>
		</form>
		<br>Note: Range can be a single day, but start and end dates must both be filled in.<br>
	</div>

	<div class="secright">
		<h2>Release a recent day:</h2>
		Unreleased IPs over the past five days. Click below to review.<br><br>
		
<?php
	include_once("config.php");
	include_once("functions.php");

	$sql = $pdo->prepare("
		SELECT 
			COUNT(id) AS value_occurrence 
		FROM hm_fwban 
		WHERE timestamp BETWEEN '{$today} 00:00:00' AND '{$today} 23:59:59' AND (flag=3 OR flag IS NULL)
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		if ($row['value_occurrence'] == 1){$singular='';}else{$singular='s';}
		echo "<a href=\"./release-date-view.php?dateFrom=".$today."&dateTo=".$today."&submit=Release\">".number_format($row['value_occurrence'])." Hit".$singular."</a> Today<br>"; 
	}
	
	$sql = $pdo->prepare("
		SELECT 
			COUNT(id) AS value_occurrence 
		FROM hm_fwban WHERE timestamp BETWEEN '{$yesterday} 00:00:00' AND '{$yesterday} 23:59:59' AND (flag=3 OR flag IS NULL)
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		if ($row['value_occurrence'] == 1){$singular='';}else{$singular='s';}
		echo "<a href=\"./release-date-view.php?dateFrom=".$yesterday."&dateTo=".$yesterday."&submit=Release\">".number_format($row['value_occurrence'])." Hit".$singular."</a> Yesterday<br>"; 
	}
	
	$sql = $pdo->prepare("
		SELECT 
			COUNT(id) AS value_occurrence 
		FROM hm_fwban 
		WHERE timestamp BETWEEN '{$twodaysago} 00:00:00' AND '{$twodaysago} 23:59:59' AND (flag=3 OR flag IS NULL)
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		if ($row['value_occurrence'] == 1){$singular='';}else{$singular='s';}
		echo "<a href=\"./release-date-view.php?dateFrom=".$twodaysago."&dateTo=".$twodaysago."&submit=Release\">".number_format($row['value_occurrence'])." Hit".$singular."</a> on ".date("l", strtotime($twodaysago))."<br>"; 
	}
	
	$sql = $pdo->prepare("
		SELECT 
			COUNT(id) AS value_occurrence 
		FROM hm_fwban 
		WHERE timestamp BETWEEN '{$threedaysago} 00:00:00' AND '{$threedaysago} 23:59:59' AND (flag=3 OR flag IS NULL)
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		if ($row['value_occurrence'] == 1){$singular='';}else{$singular='s';}
		echo "<a href=\"./release-date-view.php?dateFrom=".$threedaysago."&dateTo=".$threedaysago."&submit=Release\">".number_format($row['value_occurrence'])." Hit".$singular."</a> on ".date("l", strtotime($threedaysago))."<br>"; 
	}
	
	$sql = $pdo->prepare("
		SELECT 
			COUNT(id) AS value_occurrence 
		FROM hm_fwban 
		WHERE timestamp BETWEEN '{$fourdaysago} 00:00:00' AND '{$fourdaysago} 23:59:59' AND (flag=3 OR flag IS NULL)
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		if ($row['value_occurrence'] == 1){$singular='';}else{$singular='s';}
		echo "<a href=\"./release-date-view.php?dateFrom=".$fourdaysago."&dateTo=".$fourdaysago."&submit=Release\">".number_format($row['value_occurrence'])." Hit".$singular."</a> on ".date("l", strtotime($fourdaysago))."<br>"; 
	}
?>
	</div>
	<div class="clear"></div>
</div>

<div class="section">
	<div class="secleft">
		<h2>Release a Ban Reason:</h2>
		Unreleased IPs for the following ban reasons. Click to review.<br><br>
<?php
	include_once("config.php");
	include_once("functions.php");

	$sql = $pdo->prepare("
		SELECT 
			ban_reason, 
			COUNT(ban_reason) AS value_occurrence 
		FROM hm_fwban 
		WHERE flag=3 OR flag IS NULL 
		GROUP BY ban_reason 
		ORDER BY value_occurrence DESC
	");
	$sql->execute();
	while($row = $sql->fetch(PDO::FETCH_ASSOC)){
		if ($row['value_occurrence'] == 1){$singular='';}else{$singular='s';}
		echo number_format($row['value_occurrence'])." hit".$singular." for <a href=\"./release-br-view.php?submit=Release&ban_reason=".$row['ban_reason']."\">".$row['ban_reason']."</a><br>";
	}
?>
	</div>

	<div class="secright">
		<h2>Release a Country:</h2>
		Will search for matching unreleased IPs.<br><br>
		<form autocomplete="off" action='release-country-view.php' method='GET'>
			<input type="text" id="country" name="country">
			<input type='submit' name='submit' value='Review' />
		</form>
	</div>
	<div class="clear"></div>
</div>

<div class="section">
	<div class="secleft">
		<h2>Release an IP range:</h2>
		Will release any matching presently banned IPs within the range and remove firewall rule.<br><br>
		<form autocomplete="off" action='release-iprange.php' method='GET'>
			<input type="text" pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\/(2[2-9]|3[0-2]))?$" title="255.255.255.255 OR 255.255.255.255/23" id="ipRange" name="ipRange">
			<input type="submit" name="submit" value="Review">
		</form>
		<br>IP Ranges MUST be in: <br>
		<b>255.255.255.255</b> OR <br>
		<b>255.255.255.255/24</b> format. <br><br>
		Single IPs will be automatically converted to /32 CIDR for search purposes. Netmask /22 - /32 only. Click to review options before committing to firewall rule removal.
	</div>

	<div class="secright">
		<h2>Release something else:</h2>
		Whatever shall we release? Work in progress placeholder. Suggestions welcome.
	</div>
	<div class="clear"></div>
</div>

<?php include("foot.php") ?>