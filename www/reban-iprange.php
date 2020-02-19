<?php include("head.php") ?>

<div class="section">

<?php
	include_once("config.php");
	include_once("functions.php");

	if (isset($_GET['page'])) {
		$page = $_GET['page'];
		$display_pagination = 1;
	} else {
		$page = 1;
		$total_pages = 1;
		$display_pagination = 0;
	}
	if (isset($_GET['submit'])) {$button = $_GET ['submit'];} else {$button = "";}
	if (isset($_GET['ipRange'])) {
		if(preg_match("/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/", ($_GET['ipRange']))) {
			$ipRange = $_GET['ipRange']."/32";
		} else if (preg_match("/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\/(2[2-9]|3[0-2]))$/", ($_GET['ipRange']))) {
			$ipRange = $_GET['ipRange'];
		} else {
			$ipRange = "";
		}
	} else {
		$ipRange = "";
	}

	if (empty($ipRange)){ 
		echo "Error: <br>- no IP range specified or <br>- malformed IP range/CIDR or <br>- CIDR outside program bounds of /22 to /32"; 
	} else {

		$ips = ipRangeFinder($ipRange);
		$iplo = $ips[0];
		$iphi = $ips[1];

		$range = explode("/", $ipRange);
		$rcidr = $range[1]; 
		$ip_count = 1 << (32 - $rcidr);

		$no_of_records_per_page = 20;
		$offset = ($page-1) * $no_of_records_per_page;

		$total_pages_sql = $pdo->prepare("
			SELECT COUNT(*) AS count 
			FROM hm_fwban 
			WHERE ".DBIpStringToIntField('ipaddress')." BETWEEN ".DBIpStringToIntValue($iplo)." AND ".DBIpStringToIntValue($iphi)."
		");
		$total_pages_sql->execute();
		$total_rows = $total_pages_sql->fetchColumn();
		$total_pages = ceil($total_rows / $no_of_records_per_page);


		$sql = $pdo->prepare("
			SELECT 
				id, 
				".DBFormatDate('timestamp', '%y/%m/%d %T')." as TimeStamp, 
				ipaddress, 
				ban_reason, 
				country, 
				flag 
			FROM hm_fwban 
			WHERE ".DBIpStringToIntField('ipaddress')." BETWEEN ".DBIpStringToIntValue($iplo)." AND ".DBIpStringToIntValue($iphi)." 
			".DBLimitRowsWithOffset('TimeStamp','DESC',0,0,$offset,$no_of_records_per_page)
		);
		$sql->execute();

		if ($total_rows == 1){$singular = '';} else {$singular= 's';}

		echo "<h2>IP Range Information</h2>";
		echo "<table class='section'>
			<tr>
				<th>IP Range</th>
				<th>Network Address</th>
				<th>Broadcast Address</th>
				<th>IPs in range</th>
			</tr>
			<tr>
				<td style=\"text-align:center;\">".$ipRange."</td>
				<td style=\"text-align:center;\">".$iplo."</td>
				<td style=\"text-align:center;\">".$iphi."</td>
				<td style=\"text-align:center;\">".$ip_count."</td>
			</tr>
			</table><br><br>";

		echo "<h2>What would you like to ban?</h2>";
		echo "Click \"YES\" under column \"RS\" to reban a single address (if previously released).<br><br>";
		echo "<a href=\"./reban-ip.php?ipRange=".$ipRange."&submit=Ban\" onclick=\"return confirm('Are you sure you want to ban all ".number_format($ip_count)." IPs in range ".$ipRange."?')\">Click here</a> to ban all <b>".number_format($ip_count)."</b> IPs in range. Duplicates will be deleted from the database prior to adding firewall rules.<br>";
		echo "<br><br>";
		if ($total_pages == 0) {
			echo "No <b>existing</b> results from Firewall Ban found within IP range <b>".$ipRange."</b>";
		} else {
			echo "Firewall Ban results for IP range \"<b>".$ipRange."</b>\": ".number_format($total_rows)." IP".$singular." (Page: ".number_format($page)." of ".number_format($total_pages).")<br>";
			echo "<table class='section'>
				<tr>
					<th>Timestamp</th>
					<th>IP Address</th>
					<th>Reason</th>
					<th>Country</th>
					<th style=\"text-align:center;\">RS</th>
				</tr>";
			while($row = $sql->fetch(PDO::FETCH_ASSOC)){

				echo "<tr>";

				echo "<td>".$row['TimeStamp']."</td>";
				echo "<td><a href=\"search.php?submit=Search&search=".$row['ipaddress']."\">".$row['ipaddress']."</a></td>";
				echo "<td>".$row['ban_reason']."</td>";
				echo "<td><a href=\"https://ipinfo.io/".$row['ipaddress']."\"  target=\"_blank\">".$row['country']."</a></td>";
				if($row['flag'] == 1 || $row['flag'] == 2) echo "<td style=\"text-align:center;\"><a href=\"./reban-ip.php?submit=Reban&ipRange=".$row['ipaddress']."\" onclick=\"return confirm('Are you sure you want to reban ".$row['ipaddress']."?')\">YES</a></td>";
			elseif($row['flag']==NULL||$row['flag']==3||$row['flag']==4||$row['flag']==7) echo "<td style=\"text-align:center;\">NO</td>";
			elseif($row['flag'] == 6 || $row['flag'] == 5) echo "<td style=\"text-align:center;\">SAF</td>";
			else echo "<td style=\"text-align:center;\">ERR</td>";

				echo "</tr>";
			}
			echo "</table>";

			if ($total_pages < 2){
				echo "";
			} else {
				echo "<ul>";
					if($page <= 1){echo "<li>First </li>";} else {echo "<li><a href=\"?submit=Search&ipRange=".$ipRange."&page=1\">First </a><li>";}
					if($page <= 1){echo "<li>Prev </li>";} else {echo "<li><a href=\"?submit=Search&ipRange=".$ipRange."&page=".($page - 1)."\">Prev </a></li>";}
					if($page >= $total_pages){echo "<li>Next </li>";} else {echo "<li><a href=\"?submit=Search&ipRange=".$ipRange."&page=".($page + 1)."\">Next </a></li>";}
					if($page >= $total_pages){echo "<li>Last</li>";} else {echo "<li><a href=\"?submit=Search&ipRange=".$ipRange."&page=".$total_pages."\">Last</a></li>";}
				echo "</ul>";
			}
		}
	}
	
	// }
?>
</div>

<?php include("foot.php") ?>