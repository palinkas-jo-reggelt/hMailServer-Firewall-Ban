<?php include("head-g.php") ?>

<div class="section">
	<div class="secleft">
		<h2>IPs added and blocked from inception:</h2>
		<div id="chart_combined"></div>
	</div>
	<div class="secright">
		<h2>Average hits per hour from inception:</h2>
		<div id="chart_hitsperhour"></div>
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
	echo "<h2>This Week's Daily Hits:</h2>";

	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$today} 00:00:00' AND '{$today} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./search.php?search=".$today."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> Today<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$yesterday} 00:00:00' AND '{$yesterday} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./search.php?search=".$yesterday."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> Yesterday<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$twodaysago} 00:00:00' AND '{$twodaysago} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./search.php?search=".$twodaysago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> on ".date("l", strtotime($twodaysago))."<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$threedaysago} 00:00:00' AND '{$threedaysago} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./search.php?search=".$threedaysago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> on ".date("l", strtotime($threedaysago))."<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$fourdaysago} 00:00:00' AND '{$fourdaysago} 23:59:59'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./search.php?search=".$fourdaysago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> on ".date("l", strtotime($fourdaysago))."<br />"; }

	echo "<br />";
	echo "</div>";

	echo "<div class=\"secright\">";

	echo "<h2>This Year's Monthly Hits:</h2>";

	$sql = "SELECT COUNT(`id`) AS `value_occurrence`, DATE_FORMAT(timestamp, '%Y-%m') AS month FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$thismonth}-01 00:00:00' AND NOW()";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./search.php?search=".$thismonth."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> so far this month<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence`, DATE_FORMAT(timestamp, '%Y-%m') AS month FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$lastmonth}-01 00:00:00' AND '{$thismonth}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./search.php?search=".$lastmonth."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($lastmonth))."<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence`, DATE_FORMAT(timestamp, '%Y-%m') AS month FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$twomonthsago}-01 00:00:00' AND '{$lastmonth}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./search.php?search=".$twomonthsago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($twomonthsago))."<br />"; }

	$sql = "SELECT COUNT(`id`) AS `value_occurrence`, DATE_FORMAT(timestamp, '%Y-%m') AS month FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$threemonthsago}-01 00:00:00' AND '{$twomonthsago}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./search.php?search=".$threemonthsago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($threemonthsago))."<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence`, DATE_FORMAT(timestamp, '%Y-%m') AS month FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$fourmonthsago}-01 00:00:00' AND '{$threemonthsago}-01 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./search.php?search=".$fourmonthsago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($fourmonthsago))."<br />"; }

	echo "<br />";
	echo "</div><div class=\"clear\"></div>";


	$sql = "SELECT `country`, COUNT(`country`) AS `value_occurrence` FROM `hm_fwban` GROUP BY `country` ORDER BY `value_occurrence` DESC LIMIT 5";
	$res_data = mysqli_query($con,$sql);
	echo "<div class=\"secleft\">";
	echo "<h2>Top 5 spammer countries:</h2>";
	while($row = mysqli_fetch_array($res_data)){
		if ($row['value_occurrence']==1){$singular="";}else{$singular="s";}
		echo "<a href=\"./search.php?submit=Search&search=".$row['country']."\">".$row['country']."</a> with ".number_format($row['value_occurrence'])." hit".$singular.".<br />";
	}
	echo "<br />";
	echo "</div>";

	$num_dups_sql = "SELECT count(*) AS duplicate_count FROM ( SELECT ipaddress FROM hm_fwban GROUP BY ipaddress HAVING COUNT(ipaddress) > 1 ) AS t";
	$result = mysqli_query($con,$num_dups_sql);
	$num_dups = mysqli_fetch_array($result)[0];
	$sql = "SELECT ipaddress, COUNT(ipaddress) AS dupip, MAX(DATE_FORMAT(timestamp, '%y/%c/%e')) AS dupdate, country FROM hm_fwban GROUP BY ipaddress HAVING dupip > 1 ORDER BY DATE(timestamp) DESC LIMIT 5";
	$res_data = mysqli_query($con,$sql);
	echo "<div class=\"secright\">";
	echo "<h2>Last 5 duplicate IPs:</h2>";
	if ($num_dups == 0){
		echo "There are no duplicate IPs to report.<br /><br />";
	}else{
		while($row = mysqli_fetch_array($res_data)){
			echo "<a href=\"./search.php?submit=Search&search=".$row['ipaddress']."\">".$row['ipaddress']."</a> with ".$row['dupip']." hits last seen ".$row['dupdate']."<br />";
		}
		if ($num_dups > 5){echo "<br />Full list of <a href=\"./duplicates.php\">Duplicate Entries</a>.<br /><br />";}
	}
	echo "</div><div class=\"clear\"></div>";
	

	echo "<div class=\"secleft\">";
	echo "<h2>Ban Reasons:</h2>";
	$sql = "SELECT `ban_reason`, COUNT(`ban_reason`) AS `value_occurrence` FROM `hm_fwban` GROUP BY `ban_reason` ORDER BY `value_occurrence` DESC";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){
		if ($row['value_occurrence']==1){$singular="";}else{$singular="s";}
		echo number_format($row['value_occurrence'])." hit".$singular." for <a href=\"./search.php?submit=Search&search=".$row['ban_reason']."\">".$row['ban_reason']."</a>.<br />";
	}
	echo "<br />";
	echo "</div>";

	echo "<div class=\"secright\">";
	echo "<h2>IPs Released From Firewall:</h2>";
	$sqlcount = "SELECT COUNT(*) FROM `hm_fwban` WHERE (flag=1 OR flag=2)";
	$res_count = mysqli_query($con,$sqlcount);
	$total_rows = mysqli_fetch_array($res_count)[0];
	if ($total_rows > 0) { 
		$sql = "SELECT `ban_reason`, COUNT(`ban_reason`) AS `value_occurrence` FROM `hm_fwban` WHERE (flag=1 OR flag=2) GROUP BY `ban_reason` ORDER BY `value_occurrence` DESC";
		$res_data = mysqli_query($con,$sql);
		while($row = mysqli_fetch_array($res_data)){
		if ($row['value_occurrence']==1){$singular="";}else{$singular="s";}
		echo "<a href=\"./rel.php?submit=Search&search=".$row['ban_reason']."\">".number_format($row['value_occurrence'])." IP".$singular."</a> triggered by ".$row['ban_reason']." released.<br />";
		}
	} else {
		echo "There are no released IPs to report.";
	}
	echo "<br />";
	echo "</div><div class=\"clear\"></div>";

	echo "<div class=\"secleft\">";
	echo "<h2>Ban Enforcement:</h2>";
	echo "<table>";
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban`WHERE flag IS NULL OR flag=1";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<tr><td style=\"text-align:right\">".number_format($row['value_occurrence'])."</td><td>Total number of IPs banned</td></tr>"; }

	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE flag=1";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<tr><td style=\"text-align:right\">-".number_format($row['value_occurrence'])."</td><td>Number of IPs released from firewall</td></tr>"; }

	echo "<tr><td style=\"text-align:right\">--------</td><td></td></tr>";

	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE flag IS NULL";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<tr><td style=\"text-align:right\">".number_format($row['value_occurrence'])."</td><td>Number of IPs currently banned by firewall rule</td></tr>"; }
	
	echo "</table>";
	echo "<br />";
	echo "</div>";

	echo "<div class=\"secright\">";
	echo "<h2>Special: IP Ranges banned:</h2>";
	$sql = "SELECT COUNT(`ipaddress`) AS `value_occurrence` FROM `hm_fwban` WHERE `ipaddress` LIKE '%.0/24'";
	$res_data = mysqli_query($con,$sql);
	$total_rows = mysqli_fetch_array($res_data)[0];
	if ($total_rows==1){$singular="";}else{$singular="s";}
	echo "<a href=\"./search.php?submit=Search&search=.0/24\">".$total_rows." hit".$singular."</a> for CIDR bans (0.0.255.0/24 IP ranges).<br />";
	

	echo "<br />";
	echo "</div><div class=\"clear\"></div>";

	echo "<div class=\"secleft\">";
	echo "<h2>Unprocessed IPs:</h2>";
	echo "IPs that have been recently added or marked for release or reban that have not yet been processed by the scheduled task to have their firewall rule added or deleted.<br /><br />";

	$sql_new = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE flag=4";
	$res_data_new = mysqli_query($con,$sql_new);
	$total_rows = mysqli_fetch_array($res_data_new)[0];
	if ($total_rows==1){$singular="";}else{$singular="s";}
	echo "<a href=\"./np.php?submit=Search&flag=4\">".number_format($total_rows)." IP".$singular."</a> recently added<br />";

	$sql_rel = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE flag=2";
	$res_data_rel = mysqli_query($con,$sql_rel);
	$total_rows = mysqli_fetch_array($res_data_rel)[0];
	if ($total_rows==1){$singular="";}else{$singular="s";}
	echo "<a href=\"./np.php?submit=Search&flag=2\">".number_format($total_rows)." IP".$singular."</a> marked for release<br />";

	$sql_reb = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE flag=3";
	$res_data_reb = mysqli_query($con,$sql_reb);
	$total_rows = mysqli_fetch_array($res_data_reb)[0];
	if ($total_rows==1){$singular="";}else{$singular="s";}
	echo "<a href=\"./np.php?submit=Search&flag=3\">".number_format($total_rows)." IP".$singular."</a> marked for reban<br />";

	echo "<br />";
	echo "</div>";

	$num_repeats_sql = "SELECT COUNT(DISTINCT(ipaddress)) FROM hm_fwban_rh";
	$result = mysqli_query($con,$num_repeats_sql);
	$num_repeats = mysqli_fetch_array($result)[0];
	$sql_repeats = "SELECT COUNT(ipaddress) AS countip, ipaddress FROM hm_fwban_rh GROUP BY ipaddress ORDER BY countip DESC LIMIT 5";
	$res_data = mysqli_query($con,$sql_repeats);
	echo "<div class=\"secright\">";
	echo "<h2>Top 5 Repeat Spammers:</h2>";
	echo "Parsed from the firewall log dropped connections: IPs that knocked on the door but couldn't get in.<br /><br />";
	if ($num_repeats == 0){
		echo "There are no repeat firewall drops to report.<br /><br />";
	}else{
		while($row = mysqli_fetch_array($res_data)){
			if ($row['countip']==1){$singular="";}else{$singular="s";}
			$sql_country = "SELECT country FROM hm_fwban WHERE ipaddress='".$row['ipaddress']."'";
			$res_country = mysqli_query($con,$sql_country);
			$country = mysqli_fetch_array($res_country)[0];
			echo number_format($row['countip'])." knock".$singular." by <a href=\"./repeats-ip.php?submit=Search&repeatIP=".$row['ipaddress']."\">".$row['ipaddress']."</a> from ".$country."<br />";
		}
		if ($num_repeats > 5){
			$res_total_repeat_count = mysqli_query($con,"SELECT COUNT(ipaddress) FROM hm_fwban_rh");
			$total_repeats = mysqli_fetch_array($res_total_repeat_count)[0];
			echo "<a href=\"./repeats-view.php\"><br />".number_format($num_repeats)." IPs</a> have repeatedly attempted to gain access unsuccessfully a total of ".number_format($total_repeats)." times.<br /><br />";}
	}

	echo "<br />";
	echo "</div><div class=\"clear\"></div>";

?>
</div>
<?php include("foot.php") ?>