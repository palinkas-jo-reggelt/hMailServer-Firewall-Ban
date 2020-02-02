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
	if (isset($_GET['submit'])) {$button = $_GET ['submit'];} else {$button = "";}
	if (isset($_GET['id'])) {$id = (mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['id']))));} else {$id = "";}
	if (isset($_GET['ipRange'])) {
		if(preg_match("/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/", ($_GET['ipRange']))) {
			$ipRange = (mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['ipRange']))))."/32";
		} else if (preg_match("/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\/(2[2-9]|3[0-2]))$/", ($_GET['ipRange']))) {
			$ipRange = mysqli_real_escape_string($con, preg_replace('/\s+/', ' ',trim($_GET['ipRange'])));
		} else {
			$ipRange = "";
		}
	} else {
		$ipRange = "";
	}

	function ipRangeFinder($cidr) {
	   $range = array();
	   $cidr = explode('/', $cidr);
	   $range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int)$cidr[1]))));
	   $range[1] = long2ip((ip2long($range[0])) + pow(2, (32 - (int)$cidr[1])) - 1);
	   return $range;
	}

	$ips = ipRangeFinder($ipRange);
	$iplo = $ips[0];
	$iphi = $ips[1];

	$range = explode("/", $ipRange);
	$rcidr = $range[1]; 
	$ip_count = 1 << (32 - $rcidr);

	echo "<H2>Ban IP Range</H2>";
	echo $ipRange." : IP Range<br /><br />";
	echo $iplo." : Network Address<br />";
	echo $iphi." : Broadcast Address<br />";
	echo $ip_count." : Number of IPs in range<br /><br />";
	echo "Begin Update:<br /><br />";


	if (empty($ipRange)){
		echo "Error: IP range empty. Please see administrator.<br /><br />";
	} else {

		$start = ip2long($iplo);
		for ($i = 0; $i < $ip_count; $i++) {

			$ip = long2ip($start + $i);
			
			$sql_existing = "SELECT id, ipaddress, flag FROM hm_fwban WHERE INET_ATON(ipaddress) = INET_ATON('".$ip."')";
			$res_existing = mysqli_query($con,$sql_existing);
			while($row = mysqli_fetch_array($res_existing)){
				$ipaddressdb = $row['ipaddress'];
				$flag = $row['flag'];
				$id = $row['id'];
			}
			if (empty($ipaddressdb)){
				// echo "IP ".$ip." not found in firewall ban database - no action taken<br />";
			// } else {
				if (!(($flag==1)||($flag==2)||($flag==5)||($flag==6))){
					$sql_update_manban = "UPDATE hm_fwban SET flag=2 WHERE id=".$id;
					$result = mysqli_query($con,$sql_update_manban);
					if(!$result){ die("Could not update data: ".mysqli_error()); }
					echo "IP ".$ip." marked for release - added to list for firewall rule removal<br />";
				} else if ($flag==5||$flag=6){
					echo "IP ".$ip." marked SAFE - no action neccessary<br />";
				} else {
					echo "IP ".$ip." previously released - no action neccessary<br />";
				}					
			}
			$ipaddressdb = "";
			$flag = "";
			$id = "";
		}
		mysqli_close($con);
	}
?>
</div>

<?php include("foot.php") ?>