<?php include("head-r.php") ?>
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
	if (isset($_GET['dateFrom'])) {$dateFrom = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['dateFrom'])));} else {$dateFrom = "";}
	if (isset($_GET['dateTo'])) {$dateTo = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['dateTo'])));} else {$dateTo = "";}
	if (isset($_GET['RS'])) {$RS = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['RS'])));} else {$RS = "";}
  
	echo "<div class='section'>";
	echo "<h2>Search a date range for connections blocked by firewall:</h2>";
	echo "Enter start & end dates and click to search.<br /><br />";
	echo "<form autocomplete='off' action='repeats-date.php' method='GET'>";
	echo "<table>";
	echo "<tr><td>Starting Date: </td><td><input type='text' id='dateFrom' name='dateFrom' placeholder='Starting Date...' value='".$dateFrom."' /></td></tr>";
	echo "<tr><td>Ending Date: </td><td><input type='text' id='dateTo' name='dateTo' placeholder='Ending Date...' value='".$dateTo."' /></td></tr>";
	echo "<tr><td><input type='submit' name='submit' value='Search' /></td></tr>";
	echo "</table>";
	echo "</form>";
	echo "</div>";
	echo "<div class='section'>";

	if (empty($dateFrom)){
		echo "Note: Range can be a single day, but start and end dates must both be filled in.<br /><br />";
	} elseif (empty($dateTo)){
		echo "You did not put in an ending date. Both beginning and ending dates are required for date range release even if the range is a single day.<br /><br />";
	} else {
  
		$no_of_records_per_page = 20;
		$offset = ($page-1) * $no_of_records_per_page;
		
		$total_pages_sql = "SELECT COUNT(DISTINCT(ipaddress)) FROM hm_fwban_rh";
		$result = mysqli_query($con,$total_pages_sql);
		$total_rows = mysqli_fetch_array($result)[0];
		$total_pages = ceil($total_rows / $no_of_records_per_page);

		$sql = "SELECT ipaddress, COUNT(ipaddress) AS countip, DATE_FORMAT(timestamp, '%y/%m/%d %H:%i.%s') as TimeStamp FROM hm_fwban_rh GROUP BY ipaddress ORDER BY timestamp DESC LIMIT $offset, $no_of_records_per_page";
		$res_data = mysqli_query($con,$sql);

		if ($total_rows == 1){$singular = '';} else {$singular= 's';}
		if ($total_rows == 0){
			echo "<br /><br />No results for date range \"<b>".$dateFrom."</b>\" to \"<b>".$dateTo."</b>\"".$RSres;
		} else {
			echo "Results for date range \"<b>".$dateFrom."</b>\" to \"<b>".$dateTo."</b>\": ".number_format($total_rows)." IP".$singular." (Page: ".number_format($page)." of ".number_format($total_pages).")<br />";
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
				if($flag === NULL || $flag == 3) echo "<td style=\"text-align:center;\"><a href=\"./release-ip.php?submit=Release&ipRange=".$row['ipaddress']."\" onclick=\"return confirm('Are you sure you want to release ".$row['ipaddress']."?')\">No</a></td>";
				elseif($flag == 4) echo "<td style=\"text-align:center;\">NP</td>";
				else echo "<td style=\"text-align:center;\">YES</td>";
				echo "</tr>";
			}
			echo "</table>";
			if ($total_pages == 1){echo "";}
			else {
				echo "<ul>";
				if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&RS=".$RS."&page=1\">First </a><li>";}
				if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&RS=".$RS."&page=".($page - 1)."\">Prev </a></li>";}
				if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&RS=".$RS."&page=".($page + 1)."\">Next </a></li>";}
				if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&RS=".$RS."&page=".$total_pages."\">Last</a></li>";}
				echo "</ul>";
			}
			echo "<br />RS = Released Status (removal from firewall). Clicking on \"NO\" will release the IP. \"NP\" = Not Processed yet.<br />RH = Repeat Hits: hits scraped from the firewall log to see how many IPs have returned, had their connection dropped and how many times.";
		}
	mysqli_close($con);
	}
	echo "<br />";
	echo "</div>";
?>

<?php include("foot.php") ?>