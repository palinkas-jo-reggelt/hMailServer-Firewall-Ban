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
		
		$total_pages_sql = "SELECT COUNT(DISTINCT(ipaddress)) FROM hm_fwban_rh WHERE DATE(timestamp) BETWEEN '{$dateFrom}%' AND '{$dateTo}%'";
		$result = mysqli_query($con,$total_pages_sql);
		$total_rows = mysqli_fetch_array($result)[0];
		$total_pages = ceil($total_rows / $no_of_records_per_page);

		// $sql = "SELECT ipaddress, COUNT(ipaddress) AS countip, DATE_FORMAT(timestamp, '%y/%m/%d %H:%i.%s') as TimeStamp FROM hm_fwban_rh WHERE DATE(timestamp) BETWEEN '{$dateFrom}%' AND '{$dateTo}%' GROUP BY ipaddress ORDER BY timestamp DESC LIMIT $offset, $no_of_records_per_page";

		$sql = "
		SELECT
			a.TimeStamp,
			a.ipaddress,
			b.ban_reason,
			b.country,
			a.countip
		FROM
		(
			SELECT ipaddress, COUNT(ipaddress) AS countip, DATE_FORMAT(timestamp, '%y/%m/%d') as TimeStamp
			FROM hm_fwban_rh
			WHERE DATE(timestamp) BETWEEN '2019-11-01%' AND '2019-11-03%' 
			GROUP BY ipaddress
		) AS a
		JOIN
		(
			SELECT ipaddress, country, ban_reason
			FROM hm_fwban
		) AS b
		ON a.ipaddress = b.ipaddress
		GROUP BY a.ipaddress
		ORDER BY a.TimeStamp DESC
		LIMIT ".$offset.", ".$no_of_records_per_page;
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
				</tr>";
			while($row = mysqli_fetch_array($res_data)){
				echo "<tr>";
				echo "<td>".$row['TimeStamp']."</td>";
				echo "<td><a href=\"./repeats-IP.php?submit=Search&repeatIP=".$row['ipaddress']."\">".$row['ipaddress']."</a></td>";
				echo "<td>".$row['ban_reason']."</td>";
				echo "<td><a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$row['country']."</a></td>";
				echo "<td style=\"text-align:right;\">".number_format($row['countip'])."</td>";
				echo "</tr>";
			}
			echo "</table>";

			if ($total_pages < 2){
				echo "<br /><br />";
			} else {
				echo "<ul>";
				if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&RS=".$RS."&page=1\">First </a><li>";}
				if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&RS=".$RS."&page=".($page - 1)."\">Prev </a></li>";}
				if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&RS=".$RS."&page=".($page + 1)."\">Next </a></li>";}
				if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&RS=".$RS."&page=".$total_pages."\">Last</a></li>";}
				echo "</ul>";
			}
			if ($total_pages > 0){
				echo	"RH = Repeat Hits<br />
						RS = Released Status<br />";
			}
		}
	mysqli_close($con);
	}
	echo "<br />";
	echo "</div>";
?>

<?php include("foot.php") ?>