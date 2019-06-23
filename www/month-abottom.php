<?php
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

	echo "<td>" . $row['TimeStamp'] . "</td>";
	echo "<td><a href=\"search.php?submit=Search&search=" . $row['ipaddress'] . "\">" . $row['ipaddress'] . "</a></td>";
	echo "<td>" . $row['ban_reason'] . "</td>";
	echo "<td><a href=\"https://ipinfo.io/" . $row['ipaddress'] . "\"  target=\"_blank\">" . $row['country'] . "</a></td>";
	if($row['flag'] === NULL) echo "<td><a href=\"./release-ip.php?submit=Search&search=".$row['id']."\" onclick=\"return confirm('Are you sure you want to release this IP?')\">No</a></td>";
	else echo "<td>YES</td>";
	
	echo "</tr>";
	}
	echo "</table>";

	mysqli_close($con);
?>
<ul>
	<li><?php if($page <= 1){ echo 'First'; } else { echo "<a href=\"?page=1\">First</a>"; } ?></li>
	<li><?php if($page <= 1){ echo 'Prev'; } else {	echo "<a href=\"?page=".($page - 1)."\">Prev</a>"; } ?></li>
	<li><?php if($page >= $total_pages){ echo 'Next'; } else { echo "<a href=\"?page=".($page + 1)."\">Next</a>"; } ?></li>
	<li><?php if($page >= $total_pages){ echo 'Last'; } else { echo "<a href=\"?page=".$total_pages."\">Last</a>"; } ?></li>
</ul>

<div class="section">
RS = Released Status (removal from firewall). Clicking on "NO" will release the IP.<br />
</div></div>
<?php include("foot.php") ?>