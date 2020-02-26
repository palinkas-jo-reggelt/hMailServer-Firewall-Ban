<?php include("head.php") ?>

<div class="section">
<h2>Search Repeat Hits (Connections Dropped at Firewall)</h2>

<?php
	include_once("config.php");
	include_once("functions.php");
	include_once("blocksdata.php");

	$search_cl_array = array_map('strtolower', $country_list);
	$search_brl_array = array_map('strtolower', $ban_reason_list);

	if (isset($_GET['page'])) {
		$page = $_GET['page'];
		$display_pagination = 1;
	} else {
		$page = 1;
		$total_pages = 1;
		$display_pagination = 0;
	}
	if (isset($_GET['submit'])) {$button = $_GET ['submit'];} else {$button = "";}
	if (isset($_GET['search'])) {$search = $_GET['search'];} else {$search = "";}
	if (isset($_GET['ipdate'])) {$ipdate = $_GET['ipdate'];} else {$ipdate = "";}

	if ((empty($search)) && (empty($ipdate))) {
		$search_sql = "FROM hm_fwban_demo_rh";
		$search_list = "";
	} elseif (preg_match("/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/", $search)) {
		$search_sql = "
			FROM hm_fwban_demo_rh
			WHERE ipaddress = '{$search}'
		";
		$search_list = "matching IP: <b>".$search."</b>";
		$ipdate = "IP";
	} elseif (preg_match("/^20[0-9]{2}-[0-9]{2}$/", $search)) {
			$search_sql = "
				FROM (
					SELECT * 
					FROM hm_fwban_demo_rh 
					WHERE '".date('Y-m-d',(strtotime($search)))." 00:00:00' <= timestamp
				) AS x
				WHERE timestamp <= '".date('Y-m-t',(strtotime($search)))." 23:59:59'
			";
			$search_list = "matching month: <b>".date('F Y',(strtotime($search)))."</b>";
			$ipdate = "Date";
	} elseif (preg_match("/^20[0-9]{2}-[0-9]{2}\-[0-9]{2}$/", $search)) {
			$search_sql = "
				FROM (
					SELECT * 
					FROM hm_fwban_demo_rh 
					WHERE '".date('Y-m-d',(strtotime($search)))." 00:00:00' <= timestamp
				) AS x
				WHERE timestamp <= '".date('Y-m-d',(strtotime($search)))." 23:59:59'
			";
			$search_list = "matching date: <b>".date('Y-m-d',(strtotime($search)))."</b>";
			$ipdate = "Date";
	} elseif ((!empty($search)) && (empty($ipdate))) {
		echo "You must enter a valid IP or date (format: YYYY-MM or YYYY-MM-DD). Showing all results.";
		$search_sql = "FROM hm_fwban_demo_rh";
		$search_list = "";
	} else {
		$search_sql = "FROM hm_fwban_demo_rh";
		$search_list = "";
	}

	if ($search==""){$search_page="";}else{$search_page="&search=".$search;}
	if ($search==""){$search_all="All ";}else{$search_all="";}
	if ($ipdate==""){$ipdate_page="";}else{$ipdate_page="&ipdate=".$ipdate;}
  
	echo "<div class='section'>";
	echo "<form action='repeats-view.php' method='GET'> ";
	echo	"<input type='text' size='20' name='search' placeholder='Search...' value='".$search."'>";
	echo	" ";
	echo	"<input type='submit' name='submit' value='Search' >";
	echo "</form>";
	echo "</div>";
	echo "<div class='section'>";

	$no_of_records_per_page = 20;
	$offset = ($page-1) * $no_of_records_per_page;
	$total_pages_sql = $pdo->prepare("
		SELECT 
			COUNT(DISTINCT(ipaddress)) 
			".$search_sql
		);
	$total_pages_sql->execute();
	$total_rows = $total_pages_sql->fetchColumn();
	$total_pages = ceil($total_rows / $no_of_records_per_page);

	$sql = $pdo->prepare("
		SELECT
			a.TimeStamp,
			a.ipaddress,
			b.ban_reason,
			b.country,
			a.countip
		FROM
		(
			SELECT 
				DISTINCT(ipaddress), 
				COUNT(ipaddress) AS countip, 
				".DBFormatDate('MAX(timestamp)', '%y/%m/%d %T')." as TimeStamp
			".$search_sql."
			GROUP BY ipaddress 
		) AS a
		JOIN
		(
			SELECT 
				ipaddress, 
				country, 
				ban_reason
			FROM hm_fwban_demo
		) AS b
		ON a.ipaddress = b.ipaddress
		".DBLimitRowsWithOffset('a.TimeStamp','DESC',0,0,$offset,$no_of_records_per_page)
	);
	$sql->execute();

	if ($total_rows == 1){$singular = '';} else {$singular= 's';}
	if ($total_rows == 0){
		echo "<br>There are no repeat drops to report ".$search_list.". Please enter only IP address or date.";
	} else {
		echo $search_all."".number_format($total_rows)." IP".$singular." repeatedly dropped at firewall ".$search_list.". (Page: ".number_format($page)." of ".number_format($total_pages).")<br>";
		echo "<table class='section'>
			<tr>
				<th>Last Hit</th>
				<th>IP Address</th>
				<th>Reason</th>
				<th>Country</th>
				<th>FB</th>
			</tr>";

		while($row = $sql->fetch(PDO::FETCH_ASSOC)){
			echo "<tr>";
			echo "<td>".$row['TimeStamp']."</td>";
			echo "<td><a href=\"./repeats-IP.php?submit=Search&repeatIP=".$row['ipaddress']."\">".$row['ipaddress']."</a></td>";
			echo "<td>".$row['ban_reason']."</td>";
			echo "<td><a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$row['country']."</a></td>";
			if($row['countip']==0){echo "<td style=\"text-align:right;\">0</td>";}
			else{echo "<td style=\"text-align:right;\"><a href=\"repeats-IP.php?submit=Search&repeatIP=".$row['ipaddress']."\">".number_format($row['countip'])."</a></td>";}
			echo "</tr>";
		}
		echo "</table>";

		if ($total_pages < 2){
			echo "<br><br>";
		} else {
			echo "<ul>";
			if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?".$ipdate_page.$search_page."&page=1\">First </a><li>";}
			if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?".$ipdate_page.$search_page."&page=".($page - 1)."\">Prev </a></li>";}
			if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?".$ipdate_page.$search_page."&page=".($page + 1)."\">Next </a></li>";}
			if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?".$ipdate_page.$search_page."&page=".$total_pages."\">Last</a></li>";}
			echo "</ul>";
		}
		if ($total_pages > 0){
			echo	"FB = Firewall Blocks<br>
					RS = Released Status<br>";
		}
	}
	echo "<br>";

?>
</div>

<?php include("foot.php") ?>