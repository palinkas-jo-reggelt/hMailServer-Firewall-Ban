<?php include("head-g.php") ?>

<!-- START DIALS -->
<div class="section">
	<h2>Today's Activity:</h2>
	<div style="float:left;width:67%;">
		<div style="float:left;width:50%;">
			<center>
				<div id="todays_hits_dial"></div>
				IPs Added
			</center>
		</div>
		<div style="float:right;width:50%;">
			<center>
				<div id="todays_repeats_dial"></div>
				IPs Blocked
			</center>
		</div>
		<div class="clear"></div>
	</div>
	<div style="float:right;width:33%;">
		<center>
			<div id="todays_blocks_dial"></div>
			Total Blocks
		</center>
	</div>
	<div class="clear"></div>
</div>
<!-- END DIALS -->


<div class="section">
	<div class="secleft">
		<h2>Hits per day from inception:</h2>
		<div id="chart_combined_staticdata"></div>
	</div>
	<div class="secright">
		<h2>Total blocks per day (block frequency):</h2>
		<div id="chart_totalblocksperday_staticdata"></div>
	</div>
	<div class="clear"></div>
</div>

<div class="section">
	<div class="secleft">
		<h2>Average hits per hour from inception:</h2>
		<div id="chart_hitsperhour_staticdata"></div>
	</div>
	<div class="secright">
		<h2>Average blocks per hour from inception:</h2>
		<div id="chart_blocksperhour_staticdata"></div>
	</div>
	<div class="clear"></div>
</div>

<div class="section">
	<div class="secmap">
		<h2>Spammers around the world:</h2>
		<div id="map_div"></div>
	</div>
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
			FROM (
				SELECT * 
				FROM hm_fwban
				WHERE '{$today} 00:00:00' <= timestamp
			) AS A 
			WHERE timestamp <= '{$today} 23:59:50'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$today."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> Today<br>"; 
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM (
				SELECT * 
				FROM hm_fwban
				WHERE '{$yesterday} 00:00:00' <= timestamp
			) AS A 
			WHERE timestamp <= '{$yesterday} 23:59:50'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$yesterday."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> Yesterday<br>"; 
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM (
				SELECT * 
				FROM hm_fwban
				WHERE '{$twodaysago} 00:00:00' <= timestamp
			) AS A 
			WHERE timestamp <= '{$twodaysago} 23:59:50'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$twodaysago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> on ".date("l", strtotime($twodaysago))."<br>"; 
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM (
				SELECT * 
				FROM hm_fwban
				WHERE '{$threedaysago} 00:00:00' <= timestamp
			) AS A 
			WHERE timestamp <= '{$threedaysago} 23:59:50'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$threedaysago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> on ".date("l", strtotime($threedaysago))."<br>";
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM (
				SELECT * 
				FROM hm_fwban
				WHERE '{$fourdaysago} 00:00:00' <= timestamp
			) AS A 
			WHERE timestamp <= '{$fourdaysago} 23:59:50'
		");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$fourdaysago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> on ".date("l", strtotime($fourdaysago))."<br>"; 
		}

		echo "<br>";
		$mindate_sql = $pdo->prepare("
			SELECT 
				MIN(".DBCastDateTimeFieldAsDate('timestamp').") AS mindate 
			FROM hm_fwban
		");
		$mindate_sql->execute();
		$mindate = $mindate_sql->fetchColumn();

		// 7 day daily average
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
				echo "Daily average last 7 days: ".number_format($row['avghits'])." hits<br>"; 
			}
		}

		// 30 day daily average
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
				echo "Daily average last 30 days: ".number_format($row['avghits'])." hits<br>"; 
			}
		}
		
		// 90 day daily average
		if ($mindate > date('Y-m-d', strtotime(date('Y-m-d')." -90 day"))){
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
				".DBLimitRowsWithOffset(DBCastDateTimeFieldAsDate('timestamp'),'DESC',0,0,0,90)."
			) d
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo "Daily average last 90 days: ".number_format($row['avghits'])." hits<br>"; 
			}
		}

		// 180 day daily average
		if ($mindate > date('Y-m-d', strtotime(date('Y-m-d')." -180 day"))){
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
				".DBLimitRowsWithOffset(DBCastDateTimeFieldAsDate('timestamp'),'DESC',0,0,0,180)."
			) d
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo "Daily average last 180 days: ".number_format($row['avghits'])." hits<br>"; 
			}
		}

	?>
	<br>
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
			FROM (
				SELECT * 
				FROM hm_fwban
				WHERE '{$thismonth}-01 00:00:00' <= timestamp
			) AS A 
			WHERE timestamp <= ".DBGetCurrentDateTime()
			.(IsMSSQL() ? "GROUP BY ".DBFormatDate('timestamp', '%Y-%m')."" : "")
		);
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$thismonth."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> so far this month<br>"; 
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence, 
				".DBFormatDate('timestamp', '%Y-%m')." AS month 
			FROM (
				SELECT * 
				FROM hm_fwban
				WHERE '{$lastmonth}-01 00:00:00' <= timestamp
			) AS A 
			WHERE timestamp < '{$thismonth}-01 00:00:00'
			".(IsMSSQL() ? "GROUP BY ".DBFormatDate('timestamp', '%Y-%m')."" : "")
		);
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$lastmonth."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($lastmonth))."<br>"; 
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence, 
				".DBFormatDate('timestamp', '%Y-%m')." AS month 
			FROM (
				SELECT * 
				FROM hm_fwban
				WHERE '{$twomonthsago}-01 00:00:00' <= timestamp
			) AS A 
			WHERE timestamp < '{$lastmonth}-01 00:00:00'
			".(IsMSSQL() ? "GROUP BY ".DBFormatDate('timestamp', '%Y-%m')."" : "")
		);
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$twomonthsago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($twomonthsago))."<br>"; 
		}

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence, 
				".DBFormatDate('timestamp', '%Y-%m')." AS month 
			FROM (
				SELECT * 
				FROM hm_fwban
				WHERE '{$threemonthsago}-01 00:00:00' <= timestamp
			) AS A 
			WHERE timestamp < '{$twomonthsago}-01 00:00:00'
			".(IsMSSQL() ? "GROUP BY ".DBFormatDate('timestamp', '%Y-%m')."" : "")
		);
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$threemonthsago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($threemonthsago))."<br>";
		}
		
		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence, 
				".DBFormatDate('timestamp', '%Y-%m')." AS month 
			FROM (
				SELECT * 
				FROM hm_fwban
				WHERE '{$fourmonthsago}-01 00:00:00' <= timestamp
			) AS A 
			WHERE timestamp < '{$threemonthsago}-01 00:00:00'
			".(IsMSSQL() ? "GROUP BY ".DBFormatDate('timestamp', '%Y-%m')."" : "")
		);
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<a href=\"./search.php?search=".$fourmonthsago."&submit=Search\">".number_format($row['value_occurrence'])." Hits</a> in ".date("F", strtotime($fourmonthsago))."<br>"; 
		}

		// Monthly average last 3 months
		echo "</br>";
		if ($mindate > date('Y-m-d', strtotime(date('Y-m-1')." -3 month"))){
			echo "";
		} else {
			$sql = $pdo->prepare("
			SELECT 
				ROUND(AVG(numhits), 0) AS avghits 
			FROM (
				SELECT 
					COUNT(id) as numhits,
					".DBCastDateTimeFieldAsMonth('timestamp')." AS timestamp
				FROM hm_fwban 
				WHERE ".DBCastDateTimeFieldAsDate('timestamp')." < ".DBFormatDate(DBGetCurrentDateTime(), '%Y/%m/01')."
				GROUP BY ".DBCastDateTimeFieldAsMonth('timestamp')."
				".DBLimitRowsWithOffset(DBCastDateTimeFieldAsMonth('timestamp'),'DESC',0,0,0,3)."
			) d
		");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo "Monthly average last 3 months: ".number_format($row['avghits'])." hits<br>"; 
			}
		}

		// Monthly average last 6 months
		if ($mindate > date('Y-m-d', strtotime(date('Y-m-1')." -6 month"))){
			echo "";
		} else {
			$sql = $pdo->prepare("
				SELECT 
					ROUND(AVG(numhits), 0) AS avghits 
				FROM (
					SELECT 
						COUNT(id) as numhits,
						".DBCastDateTimeFieldAsMonth('timestamp')." AS timestamp
					FROM hm_fwban 
				WHERE ".DBCastDateTimeFieldAsDate('timestamp')." < ".DBFormatDate(DBGetCurrentDateTime(), '%Y/%m/01')."
					GROUP BY ".DBCastDateTimeFieldAsMonth('timestamp')."
					".DBLimitRowsWithOffset(DBCastDateTimeFieldAsMonth('timestamp'),'DESC',0,0,0,6)."
				) d
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo "Monthly average last 6 months: ".number_format($row['avghits'])." hits<br>"; 
			}
		}

		// Monthly average last 12 months
		if ($mindate > date('Y-m-d', strtotime(date('Y-m-1')." -12 month"))){
			echo "";
		} else {
			$sql = $pdo->prepare("
				SELECT 
					ROUND(AVG(numhits), 0) AS avghits 
				FROM (
					SELECT 
						COUNT(id) as numhits,
						".DBCastDateTimeFieldAsMonth('timestamp')." AS timestamp
					FROM hm_fwban 
				WHERE ".DBCastDateTimeFieldAsDate('timestamp')." < ".DBFormatDate(DBGetCurrentDateTime(), '%Y/%m/01')."
					GROUP BY ".DBCastDateTimeFieldAsMonth('timestamp')."
					".DBLimitRowsWithOffset(DBCastDateTimeFieldAsMonth('timestamp'),'DESC',0,0,0,12)."
				) d
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo "Monthly average last 12 months: ".number_format($row['avghits'])." hits<br>"; 
			}
		}

	?>
	<br>
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

		$sql_total = $pdo->prepare("SELECT count(*) FROM hm_fwban");
		$sql_total->execute();
		$all_rows = $sql_total->fetchColumn();

		$sql = $pdo->prepare("
			SELECT 
				country, 
				COUNT(country) AS hits
			FROM hm_fwban 
			GROUP BY country 
			".DBLimitRowsWithOffset('hits','DESC',0,0,0,5)."
		");
		$sql->execute();
		echo "<table style=\"border:none;line-height:14px;\">";
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			if ($row['hits']==1){$singular="";}else{$singular="s";}
			echo "<tr><td><a href=\"./search.php?search=".$row['country']."\">".$row['country']."</a></td><td style=\"text-align:right;\">".number_format($row['hits'])." hit".$singular." </td><td style=\"text-align:right;\"> ".round(($row['hits'] / $all_rows * 100),2)."%</td></tr>";
		}
		echo "</table";
	?>
	<br>
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

		if ($num_dups == 0){
			echo "There are no duplicate IPs to report.<br><br>";
		}else{
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
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				echo "<a href=\"./search.php?submit=Search&search=".$row['ipaddress']."\">".$row['ipaddress']."</a> with ".$row['dupip']." hits last seen ".$row['dupdate']."<br>";
			}
			if ($num_dups > 5){echo "<br>See all ".$num_dups." <a href=\"./duplicates.php\">Duplicate Entries</a>.";}
		}
	?>
	<br>
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
			echo number_format($row['value_occurrence'])." hit".$singular." for <a href=\"./search.php?submit=Search&ban_reason=".$row['ban_reason']."\">".$row['ban_reason']."</a>.<br>";
		}
	?>
	<br>
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
					COUNT(ban_reason) AS value_occurrence
				FROM hm_fwban 
				WHERE (flag=1 OR flag=2 OR flag=5 OR flag=6) 
				GROUP BY ban_reason 
				ORDER BY value_occurrence DESC
			");
			$sql->execute();
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){
				if ($row['value_occurrence']==1){$singular="";}else{$singular="s";}
				echo "<a href=\"./search.php?submit=Search&RS=YES&ban_reason=".$row['ban_reason']."\">".number_format($row['value_occurrence'])." IP".$singular."</a> triggered by ".$row['ban_reason']." released.<br>";
			}
		} else {
			echo "There are no released IPs to report.";
		}
	?>
	<br>
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
			echo "<tr><td style=\"text-align:right;color:red;\">(".number_format($row['value_occurrence']).")</td><td>Number of IPs released from firewall</td></tr>"; 
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
	<br>
	</div>
	<!-- END OF BAN ENFORCEMENT -->


	<!-- START OF BANALYZER -->
	<div class="secright">
		<h2>Banalyzer:</h2>
		Ban Analyzer: How many IPs have unsuccessfully returned to spam and how many times.<br><br>
		<a href="./blocks.php">Blocks Analyzer</a><br><br>
	<br>
	</div> 
	<!-- END OF BANALYZER -->
	<div class="clear"></div>
</div> <!-- END OF SECTION -->


<div class="section">
	<!-- START OF UNPROCESSED IPS -->
	<div class="secleft">
		<h2>Unprocessed IPs:</h2>
		IPs that have been recently added or marked for release or reban that have not yet been processed by the scheduled task to have their firewall rule added or deleted.<br><br>

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
		echo "<a href=\"./search.php?submit=Search&RS=NEW\">".number_format($total_rows)." IP".$singular."</a> recently added<br>";

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=2
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo "<a href=\"./search.php?submit=Search&RS=2\">".number_format($total_rows)." IP".$singular."</a> marked for release<br>";

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=3
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo "<a href=\"./search.php?submit=Search&RS=3\">".number_format($total_rows)." IP".$singular."</a> marked for reban<br>";

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=5
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo "<a href=\"./search.php?submit=Search&RS=5\">".number_format($total_rows)." IP".$singular."</a> marked for SAFE list<br>";

		$sql = $pdo->prepare("
			SELECT 
				COUNT(id) AS value_occurrence 
			FROM hm_fwban 
			WHERE flag=7
		");
		$sql->execute();
		$total_rows = $sql->fetchColumn();
		if ($total_rows==1){$singular="";}else{$singular="s";}
		echo "<a href=\"./search.php?submit=Search&RS=7\">".number_format($total_rows)." IP".$singular."</a> marked for SAFE list removal<br>";
	?>
	<br>
	</div>
	<!-- END OF UNPROCESSED IPS -->


	<!-- START OF TOP 5 REPEAT SPAMMERS -->
	<div class="secright">
		<h2>Top 5 Repeat Spammers:</h2>
		Parsed from the firewall log dropped connections: IPs that knocked on the door but couldn't get in.<br><br>

	<?php
		include_once("config.php");
		include_once("functions.php");
		include_once("blocksdata.php");

		echo $topfive;
		
		$sql = $pdo->prepare("
			SELECT 
				".DBFormatDate(DBCastDateTimeFieldAsDate('MIN(lasttimestamp)'), '%M %D, %Y')." AS mindate,
				COUNT(ipaddress) AS countip,
				SUM(hits) AS counthits
			FROM hm_fwban_blocks_ip
			");
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<br>".number_format($row['countip'])." IPs attempted to connect but were dropped at the firewall a total of ".number_format($row['counthits'])." times since ".$row['mindate']."<br>"; 
		}
	?>
	<br>
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
		echo "<a href=\"./search.php?submit=Search&RS=SAF\">".$total_rows." hit".$singular."</a> for permanently released (SAFE) IPs.<br>";
	?>
	<br>
	</div> 
	<!-- END OF IPS MARKED SAFE -->


	<!-- START OF RECENT ACTIVITY -->
	<div class="secright">
		<h2>Most Recent Activity:</h2>
	
	<?php
		include_once("config.php");
		include_once("functions.php");

		$sql = $pdo->prepare("
			SELECT	
				a.ipaddress,
				a.country,
				a.lasthit,
				b.hits
			FROM
			(
			SELECT 
				ipaddress, 
				country,
				".DBFormatDate('timestamp', '%T')." AS lasthit
			FROM hm_fwban
			WHERE timestamp IN (
				SELECT MAX(timestamp) FROM hm_fwban
				)
			)  a
			LEFT JOIN
			(
				SELECT 
					hits, 
					ipaddress
				FROM hm_fwban_blocks_ip
			)  b
			ON a.ipaddress = b.ipaddress
			".DBLimitRowsWithOffset(0,0,0,0,0,1)
		);
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			if ($row['hits']===NULL) {$hits=0;} else {$hits=$row['hits'];}
			if ($row['hits']==1) {$sing="";} else {$sing="s";}
			echo "<br>Last IP banned: <a href=\"./search.php?search=".$row['ipaddress']."\">".$row['ipaddress']."</a> at ".$row['lasthit']." from <a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$row['country']."</a> with ".number_format($hits)." accumulated block".$sing.".<br>"; 
		}

		$sql = $pdo->prepare("
			SELECT	
				a.ipaddress,
				b.country,
				a.lasthit,
				a.hits
			FROM
			(
			SELECT 
				ipaddress, 
				hits,
				".DBFormatDate('lasttimestamp', '%T')." AS lasthit
			FROM hm_fwban_blocks_ip
			WHERE lasttimestamp IN (
				SELECT MAX(lasttimestamp) FROM hm_fwban_blocks_ip
				)
			)  a
			LEFT JOIN
			(
				SELECT 
					country,
					ipaddress
				FROM hm_fwban
			)  b
			ON a.ipaddress = b.ipaddress
			".DBLimitRowsWithOffset(0,0,0,0,0,1)
		);
		$sql->execute();
		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			if ($row['hits']==1) {$sing="";} else {$sing="s";}
			echo "<br>Last firewall drop: <a href=\"./search.php?search=".$row['ipaddress']."\">".$row['ipaddress']."</a> at ".$row['lasthit']." from <a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$row['country']."</a> with ".number_format($row['hits'])." accumulated block".$sing."."; 
		}

	?>
	</div>
	<!-- END OF RECENT ACTIVITY -->
	<div class="clear"></div>
</div> <!-- END OF SECTION -->


<?php include("foot.php") ?>