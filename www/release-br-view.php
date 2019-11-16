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
	if (isset($_GET['submit'])){$button = $_GET ['submit'];} else {$button = "";}
	if (isset($_GET['ban_reason'])){$ban_reason = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['ban_reason'])));}else{$ban_reason="";}

	if (empty($ban_reason)){
		echo "Error. There is no ban reason. Please see administrator.<br /><br />";
	} else {
		$no_of_records_per_page = 20;
		$offset = ($page-1) * $no_of_records_per_page;
		$total_pages_sql = "SELECT Count( * ) AS count FROM hm_fwban WHERE `ban_reason` LIKE '{$ban_reason}' AND (flag IS NULL OR flag=3)";
		$result = mysqli_query($con,$total_pages_sql);
		$total_rows = mysqli_fetch_array($result)[0];
		$total_pages = ceil($total_rows / $no_of_records_per_page);

		$sql = "SELECT id, DATE_FORMAT(timestamp, '%y/%m/%d %H:%i.%s') as TimeStamp, ipaddress, ban_reason, country, helo, flag FROM hm_fwban WHERE `ban_reason` LIKE '{$ban_reason}' AND (flag IS NULL OR flag=3) ORDER BY TimeStamp DESC LIMIT $offset, $no_of_records_per_page";
		$res_data = mysqli_query($con,$sql);

		if ($total_rows == 1){$singular = '';} else {$singular= 's';}
		if ($total_rows == 0){
			echo "No unreleased results for ban reason \"<b>".$ban_reason."</b>\".";
		} else {
			echo "<h2>What would you like to release?</h2>";
			echo "Click \"NO\" under column \"RS\" to release a single address.<br /><br />";
			echo "<a href=\"./release-br.php?ban_reason=".$ban_reason."&submit=Release\" onclick=\"return confirm('Are you sure you want to release all IPs for ".$ban_reason."?')\">Click here</a> to release all.<br />";
			echo "<br /><br />";
			echo "Results for ban reason \"<b>".$ban_reason."</b>\": ".number_format($total_rows)." IP".$singular." (Page: ".number_format($page)." of ".number_format($total_pages).")<br />";
			echo "<table class='section'>
				<tr>
					<th>Timestamp</th>
					<th>IP Address</th>
					<th>Reason</th>
					<th>Country</th>
					<th>HELO</th>
					<th>RS</th>
				</tr>";
			while($row = mysqli_fetch_array($res_data)){

			echo "<tr>";

			echo "<td>" . $row['TimeStamp'] . "</td>";
			echo "<td><a href=\"search.php?submit=Search&search=" . $row['ipaddress'] . "\">" . $row['ipaddress'] . "</a></td>";
			echo "<td>" . $row['ban_reason'] . "</td>";
			echo "<td><a href=\"https://ipinfo.io/" . $row['ipaddress'] . "\"  target=\"_blank\">" . $row['country'] . "</a></td>";
			echo "<td>" . $row['helo'] . "</td>";
			if($row['flag'] === NULL || $row['flag'] == 3) echo "<td><a href=\"./release-ip.php?submit=Release&ipRange=".$row['ipaddress']."\" onclick=\"return confirm('Are you sure you want to release ".$row['ipaddress']."?')\">No</a></td>";
			else echo "<td>YES</td>";

			echo "</tr>";
			}
			echo "</table>";
			if ($total_pages == 1){echo "";}
			else {
				echo "<ul>";
				if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search&ban_reason=".$ban_reason."&page=1\">First </a><li>";}
				if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search&ban_reason=".$ban_reason."&page=".($page - 1)."\">Prev </a></li>";}
				if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search&ban_reason=".$ban_reason."&page=".($page + 1)."\">Next </a></li>";}
				if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search&ban_reason=".$ban_reason."&page=".$total_pages."\">Last</a></li>";}
				echo "</ul>";
			}
			if ($total_pages > 0){
				echo "<br />
				RS = Release Status<br /><br />";
			}
		}
		mysqli_close($con);
	}
	echo "<br />";
?>
</div>

<?php include("foot.php") ?>