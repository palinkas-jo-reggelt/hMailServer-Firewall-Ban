<?php include("head.php") ?>

<div class="section">
<h2>Search Repeat Hits (Connections Dropped at Firewall)</h2>

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
	if (isset($_GET['submit'])) {
		$button = $_GET ['submit'];
	} else {
		$button = "";
	}
	if (isset($_GET['flag'])) {$flag = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['flag'])));} else {$flag = "";}
	if (isset($_GET['search'])) {$search = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['search'])));} else {$search = "";}
	if ($search==""){$search_sql="";}else{$search_sql=" WHERE ipaddress LIKE '{$search}%' OR timestamp LIKE '{$search}%'";}
	if ($search==""){$search_page="";}else{$search_page="&search=".$search;}
	if ($search==""){$search_list="";}else{$search_list=" matching <b>\"".$search."\"</b>";}
	if ($search==""){$search_all="All ";}else{$search_all="";}
  
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
	$total_pages_sql = "SELECT COUNT(DISTINCT(ipaddress)) FROM hm_fwban_rh".$search_sql."";
	$result = mysqli_query($con,$total_pages_sql);
	$total_rows = mysqli_fetch_array($result)[0];
	$total_pages = ceil($total_rows / $no_of_records_per_page);

	$sql = "SELECT ipaddress, COUNT(ipaddress) AS countip, DATE_FORMAT(timestamp, '%y/%m/%d %H:%i.%s') as TimeStamp FROM hm_fwban_rh".$search_sql." GROUP BY ipaddress ORDER BY timestamp DESC LIMIT $offset, $no_of_records_per_page";
	$res_data = mysqli_query($con,$sql);

	if ($total_rows == 1){$singular = '';} else {$singular= 's';}
	if ($total_rows == 0){
		echo "<br />There are no repeat drops to report for search term <b>\"".$search."\"</b>. Please enter only IP address or date.";
	} else {
		echo $search_all."".number_format($total_rows)." IP".$singular." repeatedly dropped at firewall".$search_list.". (Page: ".number_format($page)." of ".number_format($total_pages).")<br />";
		echo "<table class='section'>
			<tr>
				<th>Timestamp</th>
				<th>IP Address</th>
				<th>Reason</th>
				<th>Country</th>
				<th>RH</th>
				<th>RS</th>
			</tr>";

		while($row = mysqli_fetch_array($res_data)){
			$res_country = mysqli_query($con,"SELECT country FROM hm_fwban WHERE ipaddress='".$row['ipaddress']."'");
			$country = mysqli_fetch_array($res_country)[0];
			$res_ban_reason = mysqli_query($con,"SELECT ban_reason FROM hm_fwban WHERE ipaddress='".$row['ipaddress']."'");
			$ban_reason = mysqli_fetch_array($res_ban_reason)[0];
			$res_flag = mysqli_query($con,"SELECT flag FROM hm_fwban WHERE ipaddress='".$row['ipaddress']."'");
			$flag = mysqli_fetch_array($res_flag)[0];
			echo "<tr>";
			echo "<td>".$row['TimeStamp']."</td>";
			echo "<td><a href=\"./repeats-IP.php?submit=Search&repeatIP=".$row['ipaddress']."\">".$row['ipaddress']."</a></td>";
			echo "<td>".$ban_reason."</td>";
			echo "<td><a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$country."</a></td>";
			echo "<td style=\"text-align:right;\">".number_format($row['countip'])."</td>";
			if($flag === NULL) echo "<td style=\"text-align:center;\"><a href=\"./release-ip.php?submit=Release&ipRange=".$row['ipaddress']."\" onclick=\"return confirm('Are you sure you want to release ".$row['ipaddress']."?')\">No</a></td>";
			elseif($flag == 1) echo "<td style=\"text-align:center;\">YES</td>";
			elseif($flag == 2) echo "<td style=\"text-align:center;\">NPR</td>";
			elseif($flag == 3) echo "<td style=\"text-align:center;\">NPB</td>";
			elseif($flag == 4) echo "<td style=\"text-align:center;\">NEW</td>";
			elseif($flag == 5) echo "<td style=\"text-align:center;\">NPS</td>";
			elseif($flag == 6) echo "<td style=\"text-align:center;\">SAF</td>";
			elseif($flag == 7) echo "<td style=\"text-align:center;\">SLR</td>";
			else echo "<td style=\"text-align:center;\">ERR</td>";
			echo "</tr>";
		}
		echo "</table>";

		if ($total_pages < 2){
			echo "<br /><br />";
		} else {
			echo "<ul>";
			if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search".$search_page."&page=1\">First </a><li>";}
			if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search".$search_page."&page=".($page - 1)."\">Prev </a></li>";}
			if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search".$search_page."&page=".($page + 1)."\">Next </a></li>";}
			if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search".$search_page."&page=".$total_pages."\">Last</a></li>";}
			echo "</ul>";
		}
		if ($total_pages > 0){
			echo	"RH = Repeat Hits<br />
					RS = Released Status<br />";
		}
	}

	mysqli_close($con);

	echo "<br />";
?>
</div>

<?php include("foot.php") ?>