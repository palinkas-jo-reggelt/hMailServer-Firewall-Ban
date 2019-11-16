<?php include("head.php") ?>
<?php include("cred.php") ?>

<div class="wrapper">
<div class="section">
	<h2>Block List Analyzer</h2>
	Counts the number of Banned IPs that returned and were subsequently blocked by the firewall for a given number of distinct days. <br /><br />
	Choose the number of days to run out below. Execution time is high, so start with no more than 10 days before attempting to proceed beyond that. If you run into "exceeded max execution time" errors then run BlockCount.ps1 instead.<br /><br />
	How many days to input? 
	<form autocomplete="off" action="blocks.php" method="GET">
		<select name='days' onchange='this.form.submit()'>
			<option selected value='0'>Days</option>
			<option value='5'>5</option>
			<option value='10'>10</option>
			<option value='15'>15</option>
			<option value='20'>20</option>
			<option value='25'>25</option>
			<option value='30'>30</option>
			<option value='35'>35</option>
			<option value='40'>40</option>
			<option value='45'>45</option>
			<option value='50'>50</option>
		</select>
		<noscript><input type="submit" value="Submit"></noscript>
	</form>

<?php

if (isset($_GET['submit'])) {$button = $_GET ['submit'];} else {$button = "";}
if (isset($_GET['days'])) {$days = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['days'])));} else {$days = 0;}

$tsql = "SELECT COUNT(DISTINCT(ipaddress))from hm_fwban";
$tres_data = mysqli_query($con,$tsql);
$TotalIPs = mysqli_fetch_array($tres_data)[0];

$nsql = "SELECT COUNT(DISTINCT(ipaddress))from hm_fwban_rh";
$nres_data = mysqli_query($con,$nsql);
$AllReturnIPs = mysqli_fetch_array($nres_data)[0];

$NeverIPs = ($TotalIPs - $AllReturnIPs);
$PercentNever = sprintf("%.2f%%", ($NeverIPs / $TotalIPs) * 100);

echo "<br /><br />";
echo "Total Number of Firewall Bans: ".number_format($TotalIPs)."<br /><br />";
echo "Number of Firewall Bans that have never returned: ".number_format($NeverIPs)." (".$PercentNever.")<br /><br />";

echo "<table class='section'>
	<tr>
		<th>Number of IPs</th>
		<th>Percent Returns</th>
		<th>Returned At Least</th>
	</tr>";

$a = 0;

If ($days == 0){
	echo "";
} Else {
	do{
		$sql = "SELECT COUNT(*) AS countips 
				FROM (
					SELECT 
						ipaddress, 
						COUNT(DISTINCT(DATE(timestamp))) AS countdate 
					FROM hm_fwban_rh 
					GROUP BY ipaddress 
					HAVING countdate > ".$a."
				) AS returnhits
			   ";
		$res_data = mysqli_query($con,$sql);
		$ReturnIPs = mysqli_fetch_array($res_data)[0];
		$PercentReturns = sprintf("%.2f%%", ($ReturnIPs / $TotalIPs) * 100);
		echo "<tr>";
		echo "<td style=\"text-align:right;\"><a href=\"./blocks-view.php?submit=Search&days=".($a + 1)."\">".number_format($ReturnIPs)."</a></td>";
		echo "<td style=\"text-align:right;\">".$PercentReturns."</td>";
		If ($a == 0){$sd = "";} Else {$sd = "s";}
		echo "<td style=\"text-align:center;\">".($a + 1)." day".$sd."</td>";
		echo "</tr>";

		$a++;

	} while($a < $days); 
}

echo "</table>";

?>
	<br /><br /><br /><br />
</div> <!-- end section -->
</div> <!-- end wrapper -->

<?php include("foot.php") ?>