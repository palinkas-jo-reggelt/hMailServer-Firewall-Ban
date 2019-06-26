<?php include("head.php") ?>

<div class="section">

<?php include("cred.php") ?>
<?php
	$today = date('Y-m-d');
	$yesterday = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
	$twodaysago = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-2, date("Y")));
	$threedaysago = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
	$fourdaysago = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-4, date("Y")));
	$fourdaysago = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d")-4, date("Y")));
	$thismonth = date('Y-m-1');
	$lastmonth = date('Y-m-1', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	$twomonthsago = date('Y-m-1', mktime(0, 0, 0, date("m")-2, date("d"), date("Y")));
	$threemonthsago = date('Y-m-1', mktime(0, 0, 0, date("m")-3, date("d"), date("Y")));
	$fourmonthsago = date('Y-m-1', mktime(0, 0, 0, date("m")-4, date("d"), date("Y")));

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

	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$thismonth} 00:00:00' AND NOW()";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./month-curr.php\">".number_format($row['value_occurrence'])." Hits</a> so far this month<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$lastmonth} 00:00:00' AND '{$thismonth} 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./month-last.php\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($lastmonth))."<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$twomonthsago} 00:00:00' AND '{$lastmonth} 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./month-2ma.php\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($twomonthsago))."<br />"; }

	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$threemonthsago} 00:00:00' AND '{$twomonthsago} 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./month-3ma.php\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($threemonthsago))."<br />"; }
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE `timestamp` BETWEEN '{$fourmonthsago} 00:00:00' AND '{$threemonthsago} 00:00:00'";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "<a href=\"./month-4ma.php\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($fourmonthsago))."<br />"; }

	echo "<br />";
	echo "</div><div class=\"clear\"></div>";


	$sql = "SELECT `country`, COUNT(`country`) AS `value_occurrence` FROM `hm_fwban` GROUP BY `country` ORDER BY `value_occurrence` DESC LIMIT 5";
	$res_data = mysqli_query($con,$sql);
	echo "<div class=\"secleft\">";
	echo "<h2>Top 5 spammer countries:</h2>";
	while($row = mysqli_fetch_array($res_data)){
	echo "<a href=\"./search.php?submit=Search&search=".$row['country']."\">".$row['country']."</a> with ".number_format($row['value_occurrence'])." hits.<br />";
	}
	echo "<br />";
	echo "</div>";

	$sql = "SELECT `ipaddress`, `country`, COUNT(`ipaddress`) AS `value_occurrence` FROM `hm_fwban` GROUP BY `ipaddress` ORDER BY `value_occurrence` DESC LIMIT 5";
	$res_data = mysqli_query($con,$sql);
	echo "<div class=\"secright\">";
	echo "<h2>Top 5 spammer IP's:</h2>";
	while($row = mysqli_fetch_array($res_data)){
	echo "<a href=\"./search.php?submit=Search&search=".$row['ipaddress']."\">".$row['ipaddress']."</a> from ".$row['country']." with ".$row['value_occurrence']." hits.<br />";
	}
	echo "<br />";
	echo "</div><div class=\"clear\"></div>";

	echo "<div class=\"secleft\">";
	echo "<h2>Ban Reasons:</h2>";
	$sql = "SELECT `ban_reason`, COUNT(`ban_reason`) AS `value_occurrence` FROM `hm_fwban` GROUP BY `ban_reason` ORDER BY `value_occurrence` DESC";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){
	echo number_format($row['value_occurrence'])." hits for <a href=\"./search.php?submit=Search&search=".$row['ban_reason']."\">".$row['ban_reason']."</a>.<br />";
	}
	echo "<br />";
	echo "</div>";

	echo "<div class=\"secright\">";
	echo "<h2>IPs Released From Firewall:</h2>";
	$sqlcount = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE (flag=1 OR flag=2)";
	$res_count = mysqli_query($con,$sqlcount);
	$total_rows = mysqli_fetch_array($res_count)[0];
	if ($total_rows > 0) { 
		$sql = "SELECT `ban_reason`, COUNT(`ban_reason`) AS `value_occurrence` FROM `hm_fwban` WHERE (flag=1 OR flag=2) GROUP BY `ban_reason` ORDER BY `value_occurrence` DESC";
		$res_data = mysqli_query($con,$sql);
		while($row = mysqli_fetch_array($res_data)){
		echo "<a href=\"./rel.php?submit=Search&search=".$row['ban_reason']."\">".number_format($row['value_occurrence'])." IPs</a> triggered by ".$row['ban_reason']." released.<br />";
		}
	} else {
		echo "There are no released IPs to report.";
	}
	echo "<br />";
	echo "</div><div class=\"clear\"></div>";

	echo "<div class=\"secleft\">";
	echo "<h2>Ban Enforcement:</h2>";
	
	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban`";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo number_format($row['value_occurrence'])." Total number of IPs banned<br />"; }

	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE flag=1 OR flag=2";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo "-".number_format($row['value_occurrence'])." Number of IPs released from firewall<br />"; }

	echo "--------<br />";

	$sql = "SELECT COUNT(`id`) AS `value_occurrence` FROM `hm_fwban` WHERE flag IS NULL OR flag=3";
	$res_data = mysqli_query($con,$sql);
	while($row = mysqli_fetch_array($res_data)){ echo number_format($row['value_occurrence'])." Number of IPs currently banned by firewall rule<br />"; }
	
	echo "<br />";
	echo "</div>";

	echo "<div class=\"secright\">";
	echo "<h2>Special: IP Ranges banned:</h2>";
	$sql = "SELECT COUNT(`ipaddress`) AS `value_occurrence` FROM `hm_fwban` WHERE `ipaddress` LIKE '%.0/24'";
	$res_data = mysqli_query($con,$sql);
	$total_rows = mysqli_fetch_array($res_data)[0];
	echo "<a href=\"./search.php?submit=Search&search=.0/24\">".$total_rows." hits</a> for CIDR bans (0.0.255.0/24 IP ranges).<br />";
	

	echo "<br />";
	echo "</div><div class=\"clear\"></div>";

?>
</div>
<?php include("foot.php") ?>