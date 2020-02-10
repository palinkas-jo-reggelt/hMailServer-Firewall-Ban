<?php include("head-g.php") ?>

<div class="wrapper">
<div class="section">
	<div class="secleft">
		<h2>Hits per day from inception:</h2>
		<div id="chart_combined"></div>
	</div>
	<div class="secright">
		<h2>Total blocks per day (block frequency):</h2>
		<div id="chart_totalblocksperday"></div>
	</div>
	<div class="clear"></div>
</div>

<div class="section">
	<div class="secleft">
		<h2>Average hits per hour from inception:</h2>
		<div id="chart_hitsperhour"></div>
	</div>
	<div class="secright">
		<h2>Average blocks per hour from inception:</h2>
		<div id="chart_blocksperhour"></div>
	</div>
	<div class="clear"></div>
</div>

<div class="section">
	<!-- START OF DAILY HITS -->
	<div class="secleft">
		<h2>This Week's Daily Hits:</h2>

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
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$today} 00:00:00' AND '{$today} 23:59:59'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$today."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> Today<br />"; 
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$yesterday} 00:00:00' AND '{$yesterday} 23:59:59'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$yesterday."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> Yesterday<br />"; 
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$twodaysago} 00:00:00' AND '{$twodaysago} 23:59:59'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$twodaysago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> on ".date("l", strtotime($twodaysago))."<br />"; 
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$threedaysago} 00:00:00' AND '{$threedaysago} 23:59:59'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$threedaysago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> on ".date("l", strtotime($threedaysago))."<br />";
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$fourdaysago} 00:00:00' AND '{$fourdaysago} 23:59:59'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$fourdaysago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> on ".date("l", strtotime($fourdaysago))."<br />"; 
		}

		echo "<br />";
		$mindate_sql = $pdo->prepare("
			SELECT 
				MIN(DATE(timestamp)) AS mindate 
			FROM hm_fwban
		");
		$mindate_sql->execute();
		$mindate = $mindate_sql->fetchColumn();
		if ($mindate > date('Y-m-d', strtotime(date('Y-m-d')." -7 day"))){
			echo "";
		} else {
			$sql = $pdo->prepare("
			SELECT 
				ROUND(AVG(numhits), 0) AS avghits 
			FROM (
				SELECT 
					COUNT(id) as numhits,
					".DBCastDateTimeFieldAsDate('timestamp')."
				FROM hm_fwban 
				WHERE ".DBCastDateTimeFieldAsDate('timestamp')." < ".DBCastDateTimeFieldAsDate(DBGetCurrentDateTime())."
				GROUP BY ".DBCastDateTimeFieldAsDate('timestamp')."
				".DBLimitRowsWithOffset(DBCastDateTimeFieldAsDate('timestamp'),'DESC',0,0,0,7)."
			) d
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo "Daily average last 7 days: ".number_format($row['avghits'])." hits<br />"; 
			}
		}

		if ($mindate > date('Y-m-d', strtotime(date('Y-m-d')." -30 day"))){
			echo "";
		} else {
			$sql = $pdo->prepare("
			SELECT 
				ROUND(AVG(numhits), 0) AS avghits 
			FROM (
				SELECT 
					COUNT(id) as numhits,
					".DBCastDateTimeFieldAsDate('timestamp')."
				FROM hm_fwban 
				WHERE ".DBCastDateTimeFieldAsDate('timestamp')." < ".DBCastDateTimeFieldAsDate(DBGetCurrentDateTime())."
				GROUP BY ".DBCastDateTimeFieldAsDate('timestamp')."
				".DBLimitRowsWithOffset(DBCastDateTimeFieldAsDate('timestamp'),'DESC',0,0,0,30)."
			) d
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo "Daily average last 30 days: ".number_format($row['avghits'])." hits<br />"; 
			}
		}
	?>
	<br />
	</div> 
	<!-- END OF DAILY HITS -->
	
	
	<!-- START MONTHLY HITS -->
	<div class="secright">
		<h2>This Year's Monthly Hits:</h2>

	<?php
		include_once("config.php");
		include_once("functions.php");

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence, 
				".DBFormatDate('timestamp', '%Y-%m')." AS month 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$thismonth}-01 00:00:00' AND NOW()
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$thismonth."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> so far this month<br />"; 
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence, 
				".DBFormatDate('timestamp', '%Y-%m')." AS month 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$lastmonth}-01 00:00:00' AND '{$thismonth}-01 00:00:00'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$lastmonth."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($lastmonth))."<br />"; 
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence, 
				".DBFormatDate('timestamp', '%Y-%m')." AS month 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$twomonthsago}-01 00:00:00' AND '{$lastmonth}-01 00:00:00'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$twomonthsago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($twomonthsago))."<br />"; 
		}

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence, 
				".DBFormatDate('timestamp', '%Y-%m')." AS month 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$threemonthsago}-01 00:00:00' AND '{$twomonthsago}-01 00:00:00'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$threemonthsago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($threemonthsago))."<br />";
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence, 
				".DBFormatDate('timestamp', '%Y-%m')." AS month 
			FROM hm_fwban 
			WHERE timestamp BETWEEN '{$fourmonthsago}-01 00:00:00' AND '{$threemonthsago}-01 00:00:00'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$fourmonthsago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($fourmonthsago))."<br />"; 
		}

		echo "<br />";
		if ($mindate > date('Y-m-d', strtotime(date('Y-m-1')." -3 month"))){
			echo "";
		} else {
			$sql = $pdo->prepare("
				SELECT 
					ROUND(AVG(numhits), 0) AS avghits 
				FROM (
					SELECT 
						COUNT(id) as numhits,
						".DBCastDateTimeFieldAsMonth('timestamp')."
					FROM hm_fwban 
					WHERE ".DBCastDateTimeFieldAsMonth('timestamp')." < ".DBFormatDate(DBGetCurrentDateTime(), '%Y/%m/01')."
					GROUP BY ".DBCastDateTimeFieldAsMonth('timestamp')."
					".DBLimitRowsWithOffset(DBCastDateTimeFieldAsMonth('timestamp'),'DESC',0,0,0,3)."
				) d
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo "Monthly average last 3 months: ".number_format($row['avghits'])." hits<br />"; 
			}
		}

		if ($mindate > date('Y-m-d', strtotime(date('Y-m-1')." -6 month"))){
			echo "";
		} else {
			$sql = $pdo->prepare("
				SELECT 
					ROUND(AVG(numhits), 0) AS avghits 
				FROM (
					SELECT 
						COUNT(id) as numhits,
						".DBCastDateTimeFieldAsMonth('timestamp')."
					FROM hm_fwban 
					WHERE ".DBCastDateTimeFieldAsMonth('timestamp')." < ".DBFormatDate(DBGetCurrentDateTime(), '%Y/%m/01')."
					GROUP BY ".DBCastDateTimeFieldAsMonth('timestamp')."
					".DBLimitRowsWithOffset(DBCastDateTimeFieldAsMonth('timestamp'),'DESC',0,0,0,6)."
				) d
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo "Monthly average last 6 months: ".number_format($row['avghits'])." hits<br />"; 
			}
		}
	?>
	<br />
	</div> 
	<div class="clear"></div>
	<!-- END OF MONTHLY HITS -->
</div> <!-- END OF SECTION -->


<div class="section">
	<!-- START OF TOP 5 SPAMMER COUNTRIES -->
	<div class="secleft">
		<h2>Top 5 spammer countries:</h2>

	<?php
		include_once("config.php");
		include_once("functions.php");

		$sql = $pdo->prepare("
			SELECT 
				country, 
				COUNT(country) AS value_occurrence 
			FROM hm_fwban 
			GROUP BY country 
			".DBLimitRowsWithOffset('value_occurrence','DESC',0,0,0,5)."
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			if ($row['value_occurrence']==1){$singular="";}else{$singular="s";}
			echo "<a href=\"./search.php?submit=Search&search=".$row['country']."\">".$row['country']."</a> with ".number_format($row['value_occurrence'])." hit".$singular.".<br />";
		}
	?>
	<br />
	</div> 
	<!-- END OF TOP 5 SPAMMER COUNTRIES -->
	

	<!-- START OF LAST 5 DUPLICATES -->
	<div class="secright">
		<h2>Last 5 duplicate IPs:</h2>

	<?php
		include_once("config.php");
		include_once("functions.php");

		$num_dups_sql = $pdo->prepare("
			SELECT 
				COUNT(*) AS duplicate_count 
			FROM ( 
				SELECT ipaddress 
				FROM hm_fwban 
				GROUP BY ipaddress 
				HAVING COUNT(ipaddress) > 1 
			) AS t
		");
		$num_dups_sql->execute();
		$num_dups = $num_dups_sql->fetchColumn();

		$sql = $pdo->prepare("
			SELECT 
				ipaddress, 
				COUNT(ipaddress) AS dupip, 
				MAX(".DBFormatDate('timestamp', '%y/%c/%e').") AS dupdate, 
				country 
			FROM hm_fwban 
			GROUP BY ipaddress 
			HAVING dupip > 1 
			".DBLimitRowsWithOffset('dupdate','DESC',0,0,0,5)
		);
		$sql->execute();
		if ($num_dups == 0){
			echo "There are no duplicate IPs to report.<br /><br />";
		}else{
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo "<a href=\"./search.php?submit=Search&search=".$row['ipaddress']."\">".$row['ipaddress']."</a> with ".$row['dupip']." hits last seen ".$row['dupdate']."<br />";
			}
			if ($num_dups > 5){echo "<br />See all ".$num_dups." <a href=\"./duplicates.php\">Duplicate Entries</a>.<br /><br />";}
		}
	?>
	<br />
	</div> 
	<!-- END OF LAST 5 DUPLICATES -->
	<div class="clear"></div>
</div> <!-- END OF SECTION -->
	

<div class="section">
	<!-- START OF BAN REASONS -->
	<div class="secleft">
		<h2>Ban Reasons:</h2>

	<?php
		include_once("config.php");
		include_once("functions.php");

		$sql = $pdo->prepare("
			SELECT 
				ban_reason, 
				COUNT(ban_reason) AS value_occurrence 
				FROM hm_fwban 
				GROUP BY ban_reason 
				ORDER BY value_occurrence DESC
			");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			if ($row['value_occurrence']==1){$singular="";}else{$singular="s";}
			echo number_format($row['value_occurrence'])." hit".$singular." for <a href=\"./search.php?submit=Search&ban_reason=".$row['ban_reason']."\">".$row['ban_reason']."</a>.<br />";
		}
	?>
	<br />
	</div>
	<!-- END OF BAN REASONS -->


	<!-- START OF RELEASED IPS -->
	<div class="secright">
		<h2>IPs Released From Firewall:</h2>

	<?php
		include_once("config.php");
		include_once("functions.php");

		$sqlcount = $pdo->prepare("
			SELECT 
				COUNT(*) 
			FROM hm_fwban 
			WHERE (flag=1 OR flag=2 OR flag=5 OR flag=6)
		");
		$sqlcount->execute();
		$total_rows = $sqlcount->fetchColumn();
		if ($total_rows > 0) { 
			$sql = $pdo->prepare("
				SELECT 
					ban_reason, 
					COUNT(ban_reason) AS value_occurrence, 
					flag 
				FROM hm_fwban 
				WHERE (flag=1 OR flag=2 OR flag=5 OR flag=6) 
				GROUP BY ban_reason 
				ORDER BY value_occurrence DESC
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				if ($row['value_occurrence']==1){$singular="";}else{$singular="s";}
				echo "<a href=\"./search.php?submit=Search&RS=YES&ban_reason=".$row['ban_reason']."\">".number_format($row['value_occurrence'])." IP".$singular."</a> triggered by ".$row['ban_reason']." released.<br />";
			}
		} else {
			echo "There are no released IPs to report.";
		}
	?>
	<br />
	</div> 
	<!-- END OF RELEASED IPS -->
	<div class="clear"></div>
</div> <!-- END OF SECTION -->


<div class="section">
	<!-- START OF BAN ENFORCEMENT -->
	<div class="secleft">
		<h2>Ban Enforcement:</h2>

	<?php
		include_once("config.php");
		include_once("functions.php");

		echo "<table>";
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
				FROM hm_fwban
			");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<tr><td style=\"text-align:right\">".number_format($row['value_occurrence'])."</td><td>Total number of IPs banned</td></tr>"; 
		}

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=1 OR flag=2 OR flag=5 OR flag=6
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<tr><td style=\"text-align:right\">-".number_format($row['value_occurrence'])."</td><td>Number of IPs released from firewall</td></tr>"; 
		}

		echo "<tr><td style=\"text-align:right\">--------</td><td></td></tr>";

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
				FROM hm_fwban 
				WHERE flag IS NULL OR flag=3 OR flag=4 OR flag=7
			");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<tr><td style=\"text-align:right\">".number_format($row['value_occurrence'])."</td><td>Number of IPs currently banned by firewall rule</td></tr>"; 
		}
		
		echo "</table>";
	?>
	<br />
	</div>
	<!-- END OF BAN ENFORCEMENT -->


	<!-- START OF BANALYZER -->
	<div class="secright">
		<h2>Banalyzer:</h2>
		Ban Analyzer: How many IPs have unsuccessfully returned to spam and how many times.<br /><br />
		<a href="./blocks.php">Blocks Analyzer</a><br /><br />
	<br />
	</div> 
	<!-- END OF BANALYZER -->
	<div class="clear"></div>
</div> <!-- END OF SECTION -->


<div class="section">
	<!-- START OF UNPROCESSED IPS -->
	<div class="secleft">
		<h2>Unprocessed IPs:</h2>
		IPs that have been recently added or marked for release or reban that have not yet been processed by the scheduled task to have their firewall rule added or deleted.<br /><br />

	<?php
		include_once("config.php");
		include_once("functions.php");

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=4
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo "<a href=\"./search.php?submit=Search&RS=NEW\">".number_format($total_rows)." IP".$singular."</a> recently added<br />";

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=2
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo "<a href=\"./search.php?submit=Search&RS=2\">".number_format($total_rows)." IP".$singular."</a> marked for release<br />";

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=3
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo "<a href=\"./search.php?submit=Search&RS=3\">".number_format($total_rows)." IP".$singular."</a> marked for reban<br />";

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=5
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo "<a href=\"./search.php?submit=Search&RS=5\">".number_format($total_rows)." IP".$singular."</a> marked for SAFE list<br />";

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=7
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo "<a href=\"./search.php?submit=Search&RS=7\">".number_format($total_rows)." IP".$singular."</a> marked for SAFE list removal<br />";
	?>
	<br />
	</div>
	<!-- END OF UNPROCESSED IPS -->


	<!-- START OF TOP 5 REPEAT SPAMMERS -->
	<div class="secright">
		<h2>Top 5 Repeat Spammers:</h2>
		Parsed from the firewall log dropped connections: IPs that knocked on the door but couldn't get in.<br /><br />

	<?php
		include_once("config.php");
		include_once("functions.php");

		$num_repeats_sql = $pdo->prepare("
			SELECT 
				COUNT(DISTINCT(ipaddress)) 
			FROM hm_fwban_rh
		");
		$num_repeats_sql->execute();
		$num_repeats = $num_repeats_sql->fetchColumn();

		$sql_repeats = $pdo->prepare("
			SELECT
				a.ipaddress,
				a.countip,
				b.country
			FROM
			(
				SELECT 
					COUNT(ipaddress) AS countip, 
					ipaddress
				FROM hm_fwban_rh 
				GROUP BY ipaddress 
				ORDER BY countip DESC 
			) AS a
			JOIN
			(
				SELECT ipaddress, country 
				FROM hm_fwban
			) AS b
			ON a.ipaddress = b.ipaddress
			".DBLimitRowsWithOffset('countip','DESC',0,0,0,5)."
		");
		$sql_repeats->execute();
		if ($num_repeats == 0){
			echo "There are no repeat firewall drops to report.<br /><br />";
		}else{
			while($row = $sql_repeats->fetch(PDO::FETCH_ASSOC)){
				if ($row['countip']==1){$singular="";}else{$singular="s";}
				echo number_format($row['countip'])." knock".$singular." by <a href=\"./repeats-ip.php?submit=Search&repeatIP=".$row['ipaddress']."\">".$row['ipaddress']."</a> from ".$row['country']."<br />";
			}
			if ($num_repeats > 5){
				$sql_num_repeats = $pdo->prepare("
					SELECT 
						COUNT(ipaddress) 
					FROM hm_fwban_rh
				");
				$sql_num_repeats->execute();
				$total_repeats = $sql_num_repeats->fetchColumn();
				echo "<a href=\"./repeats-view.php\"><br />".number_format($num_repeats)." IPs</a> have repeatedly attempted to gain access unsuccessfully a total of ".number_format($total_repeats)." times.<br /><br />";}
		}
	?>
	<br />
	</div> 
	<!-- END OF TOP 5 REPEAT SPAMMERS -->
	<div class="clear"></div>
</div> <!-- END OF SECTION -->


<div class="section">
	<!-- START OF IPS MARKED SAFE -->
	<div class="secleft">
		<h2>IPs Marked Safe:</h2>

	<?php
		include_once("config.php");
		include_once("functions.php");

		$sql = $pdo->prepare("
			SELECT 
				COUNT(ipaddress) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=5 OR flag=6
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo "<a href=\"./search.php?submit=Search&RS=SAF\">".$total_rows." hit".$singular."</a> for permanently released (SAFE) IPs.<br />";
	?>
	<br />
	</div> 
	<!-- END OF IPS MARKED SAFE -->
	<div class="secright"></div>
	<div class="clear"></div>
</div> <!-- END OF SECTION -->


<?php include("foot.php") ?>