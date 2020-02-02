<?php include("head.php") ?>

<div class="section">
To search for a date range <a href="./search-date.php">click here</a>.
</div>

<?php include("cred.php") ?>
<?php

	if (isset($_GET['page'])) {
		$page = $_GET['page'];
		$display_pagination = 1;
	} else {
		$page = 1;
		$total_pages = 1;
		$display_pagination = 0;
	}
	if (isset($_GET['submit'])) {$button = $_GET ['submit'];} else {$button = "";}
	if (isset($_GET['search'])) {$search = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['search'])));} else {$search = "";}
	if (isset($_GET['RS'])) {$RS = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['RS'])));} else {$RS = "";}
	if (isset($_GET['flag'])) {$flag = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['flag'])));} else {$flag = "";}
	if (isset($_GET['ban_reason'])) {$ban_reason = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['ban_reason'])));} else {$ban_reason = "";}

	echo "<div class='section'>";
	echo "<form action='search.php' method='GET'> ";
	echo	"<input type='text' size='20' name='search' placeholder='Search...' value='".$search."'>";
	echo	" ";
	echo	"<select name='RS'>";
	echo		"<option value=''>RS</option>";
	echo		"<option value='YES'>YES</option>";
	echo		"<option value='NO'>NO</option>";
	echo		"<option value='NEW'>NEW</option>";
	echo		"<option value='SAF'>SAF</option>";
	echo	"</select>";
	echo	" ";
	echo	"<input type='submit' name='submit' value='Search' >";
	echo "</form>";
	echo "</div>";
	echo "<div class='section'>";
  
	$no_of_records_per_page = 20;
	$offset = ($page-1) * $no_of_records_per_page;
	
	if ($RS=="NO"){$RS_SQL = " AND (flag IS NULL OR flag=3 OR flag=7)";}
	elseif ($RS=="YES"){$RS_SQL = " AND (flag=1 OR flag=2)";}
	elseif ($RS=="NEW"){$RS_SQL = " AND flag=4";}
	elseif ($RS=="SAF"){$RS_SQL = " AND (flag=5 OR flag=6)";}
	elseif ($RS==2){$RS_SQL = " AND flag=2";}
	elseif ($RS==3){$RS_SQL = " AND flag=3";}
	elseif ($RS==5){$RS_SQL = " AND flag=5";}
	elseif ($RS==7){$RS_SQL = " AND flag=7";}
	else {$RS_SQL = "";}

	if ($ban_reason==""){
		$ban_reason_sql="";
	} else {
		$search="";
		$ban_reason_sql=" AND ban_reason LIKE '{$ban_reason}'";
	}
	
	$total_pages_sql = "SELECT Count( * ) AS count FROM hm_fwban WHERE (timestamp LIKE '%{$search}%' OR ipaddress LIKE '%{$search}%' OR ban_reason LIKE '%{$search}%' OR country LIKE '%{$search}%' OR helo LIKE '%{$search}%' OR ptr LIKE '%{$search}%')".$ban_reason_sql."".$RS_SQL."";
	$result = mysqli_query($con,$total_pages_sql);
	$total_rows = mysqli_fetch_array($result)[0];
	$total_pages = ceil($total_rows / $no_of_records_per_page);

$sql = "
	SELECT
		a.tsf,
		a.ipaddress,
		a.ban_reason,
		a.country,
		a.flag,
		a.helo,
		a.ptr,
		b.returnhits
	FROM
	(
		SELECT DATE_FORMAT(timestamp, '%y/%m/%d %H:%i.%s') as tsf, timestamp, ipaddress, ban_reason, country, flag, helo, ptr
		FROM hm_fwban 
		WHERE (timestamp LIKE '%{$search}%' OR ipaddress LIKE '%{$search}%' OR ban_reason LIKE '%{$search}%' OR country LIKE '%{$search}%' OR helo LIKE '%{$search}%' OR ptr LIKE '%{$search}%')".$ban_reason_sql."".$RS_SQL." 
		ORDER BY timestamp DESC 
	) AS a
	LEFT JOIN
	(
		SELECT COUNT(ipaddress) AS returnhits, ipaddress, timestamp
		FROM hm_fwban_rh
		GROUP BY ipaddress
		ORDER BY timestamp DESC
	) AS b
	ON a.ipaddress = b.ipaddress
	ORDER BY a.tsf DESC
	LIMIT ".$offset.", ".$no_of_records_per_page;

	$res_data = mysqli_query($con,$sql);
	
	if ($RS=="YES"){$RSres=" with release status \"<b>YES</b>\"";} 
	elseif ($RS=="NO"){$RSres=" with release status \"<b>NO</b>\"";} 
	elseif ($RS=="NEW"){$RSres=" with release status \"<b>NEW</b>\"";} 
	elseif ($RS=="SAF"){$RSres=" with release status \"<b>SAFE</b>\"";} 
	else {$RSres = "";} 
	
	if ($search==""){$search_res="";}
	else {$search_res=" for search term \"<b>".$search."</b>\"";}

	if ($ban_reason==""){$ban_reason_res="";}
	else {$ban_reason_res=" for Ban Reason \"<b>".$ban_reason."</b>\"";}

	if ($total_rows == 1){$singular = '';} else {$singular= 's';}
	if ($total_rows == 0){
		if ($search == "" && $ban_reason == ""){
			echo "Please enter a search term";
		} else {
			echo "No results ".$search_res."".$ban_reason_res."";
		}	
	} else {
		echo "Results ".$search_res."".$ban_reason_res.": ".number_format($total_rows)." Hit".$singular." (Page: ".number_format($page)." of ".number_format($total_pages).")<br />";
		echo "<table class='section'>
			<tr>
				<th>Timestamp</th>
				<th>IP Address</th>
				<th>Reason</th>
				<th>Country</th>
				<th>HELO</th>
				<th>FB</th>
				<th>RS</th>
			</tr>";
		while($row = mysqli_fetch_array($res_data)){
			echo "<tr>";
			echo "<td>".$row['tsf']."</td>";
			echo "<td><a href=\"search.php?submit=Search&search=".$row['ipaddress']."\">".$row['ipaddress']."</a></td>";
			echo "<td>".$row['ban_reason']."</td>";
			echo "<td><a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$row['country']."</a></td>";
			if (empty($row['helo'])){$helo_row=$row['ptr'];} else {$helo_row=$row['helo'];} 
			echo "<td><a onClick=\"window.open('./ptr.php?ip=".$row['ipaddress']."','PTR','resizable,height=240,width=320'); return false;\">".$helo_row."</a><noscript>You need Javascript to use the previous link or use <a href=\"ptr.php?ip=".$row['ipaddress']."\" target=\"_blank\">PTR/detail</a></noscript>";
			if ($row['returnhits']===NULL){echo "<td style=\"text-align:right;\">0</td>";}
			else {echo "<td style=\"text-align:right;\"><a href=\"repeats-IP.php?submit=Search&repeatIP=".$row['ipaddress']."\">".number_format($row['returnhits'])."</a></td>";}
			if($row['flag'] === NULL || $row['flag'] == 3 || $row['flag'] == 7) echo "<td style=\"text-align:center;\"><a href=\"./release-ip.php?submit=Release&ipRange=".$row['ipaddress']."\" onclick=\"return confirm('Are you sure you want to release ".$row['ipaddress']."?')\">No</a></td>";
			elseif($row['flag'] == 1 || $row['flag'] == 2) echo "<td style=\"text-align:center;\">YES</td>";
			elseif($row['flag'] == 4) echo "<td style=\"text-align:center;\">NEW</td>";
			elseif($row['flag'] == 6 || $row['flag'] == 5) echo "<td style=\"text-align:center;\">SAF</td>";
			else echo "<td style=\"text-align:center;\">ERR</td>";
			echo "</tr>";
		}
		echo "</table>";

		if ($ban_reason==""){$ban_reason_page="";} else {$ban_reason_page="&ban_reason=".$ban_reason."";}

		if ($total_pages == 1){echo "";}
		else {
			echo "<ul>";
			if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search&search=".$search.$ban_reason_page."&RS=".$RS."&page=1\">First </a><li>";}
			if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search&search=".$search.$ban_reason_page."&RS=".$RS."&page=".($page - 1)."\">Prev </a></li>";}
			if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search&search=".$search.$ban_reason_page."&RS=".$RS."&page=".($page + 1)."\">Next </a></li>";}
			if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search&search=".$search.$ban_reason_page."&RS=".$RS."&page=".$total_pages."\">Last</a></li>";}
			echo "</ul>";
		}
		if ($total_pages > 0){
			echo "<br />
			FB = Firewall Blocks<br />
			RS = Release Status<br /><br />";
		}
	mysqli_close($con);
	}
	echo "<br />";
	echo "</div>";
?>
<?php include("foot.php") ?>