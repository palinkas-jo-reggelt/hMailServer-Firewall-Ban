<?php include("head.php") ?>

<div class="section">

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

	if (isset($_GET['search'])) {$search = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['search'])));} else {$search = "";}
	if (isset($_GET['dateFrom'])) {$dateFrom = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['dateFrom'])));} else {$dateFrom = "";}
	if (isset($_GET['dateTo'])) {$dateTo = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['dateTo'])));} else {$dateFrom = "";}

	if (empty($dateFrom)){
		echo "You did not put in a beginning date. Both beginning and ending dates are required for date range release even if the range is a single day.<br /><br />";
	} elseif (empty($dateTo)){
		echo "You did not put in an ending date. Both beginning and ending dates are required for date range release even if the range is a single day.<br /><br />";
	} else {

		$no_of_records_per_page = 20;
		$offset = ($page-1) * $no_of_records_per_page;
		$total_pages_sql = "SELECT Count( * ) AS count FROM hm_fwban WHERE `timestamp` BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTo} 23:59:59' AND (flag=1 OR flag=2)";
		$result = mysqli_query($con,$total_pages_sql);
		$total_rows = mysqli_fetch_array($result)[0];
		$total_pages = ceil($total_rows / $no_of_records_per_page);

		$sql = "SELECT id, DATE_FORMAT(timestamp, '%y/%m/%d %H:%i.%s') as TimeStamp, ipaddress, ban_reason, countrycode, country, flag FROM hm_fwban WHERE `timestamp` BETWEEN '{$dateFrom} 00:00:00' AND '{$dateTo} 23:59:59' AND (flag=1 OR flag=2) ORDER BY TimeStamp DESC LIMIT $offset, $no_of_records_per_page";
		$res_data = mysqli_query($con,$sql);

		if ($total_rows == 1){$singular = '';} else {$singular= 's';}
		if ($total_rows == 0){
			echo "No previously released results for date range \"<b>".$dateFrom."</b>\" to \"<b>".$dateTo."</b>\"";
		} else {
			echo "<h2>What would you like to ban?</h2>";
			echo "Click \"YES\" under column \"RS\" to re-ban a single address.<br /><br />";
			echo "<a href=\"./reban-date.php?dateFrom=".$dateFrom."&dateTo=".$dateTo."&submit=Reban\">Click here</a> to re-ban all.<br />";
			echo "<br /><br />";
			echo "Results for released IPs in date range \"<b>".$dateFrom."</b>\" to \"<b>".$dateTo."</b>\": ".number_format($total_rows)." IP".$singular." (Page: ".number_format($page)." of ".number_format($total_pages).")<br />";
			echo "<table class='section'>
				<tr>
					<th>Timestamp</th>
					<th>IP Address</th>
					<th>Reason</th>
					<th>Country</th>
					<th>RS</th>
				</tr>";
		while($row = mysqli_fetch_array($res_data)){

		echo "<tr>";

		echo "<td>".$row['TimeStamp']."</td>";
		echo "<td><a href=\"search.php?submit=Search&search=".$row['ipaddress']."\">".$row['ipaddress']."</a></td>";
		echo "<td>".$row['ban_reason']."</td>";
		echo "<td><a href=\"https://ipinfo.io/" . $row['ipaddress'] . "\"  target=\"_blank\">" . $row['country'] . "</a></td>";
		if($row['flag'] == 1 || $row['flag'] == 2) echo "<td><a href=\"./reban-ip.php?submit=Reban&id=".$row['id']."\" onclick=\"return confirm('Are you sure you want to re-ban IP ".$row['ipaddress']."?')\">YES</a></td>";
		else echo "<td>NO</td>";

		echo "</tr>";
		}
		echo "</table>";
		}
		mysqli_close($con);
	}
	echo "<br />";
?>

<ul>
	<li><?php if($page <= 1){ echo 'First'; } else { echo "<a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&page=1\">First</a>"; } ?></li>
	<li><?php if($page <= 1){ echo 'Prev'; } else {	echo "<a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&page=".($page - 1)."\">Prev</a>"; } ?></li>
	<li><?php if($page >= $total_pages){ echo 'Next'; } else { echo "<a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&page=".($page + 1)."\">Next</a>"; } ?></li>
	<li><?php if($page >= $total_pages){ echo 'Last'; } else { echo "<a href=\"?submit=Search&dateFrom=".$dateFrom."&dateTo=".$dateTo."&page=".$total_pages."\">Last</a>"; } ?></li>
</ul>
</div>

<?php include("foot.php") ?>