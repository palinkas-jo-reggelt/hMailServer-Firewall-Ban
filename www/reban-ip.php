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
	if (isset($_GET['id'])) {$id = $_GET['id'];} else {$id = "";}
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

	$ips = ipRangeFinder($ipRange);
	$iplo = $ips[0];
	$iphi = $ips[1];
	$country = ip_country($iplo);

	$range = explode("/", $ipRange);
	$rcidr = $range[1]; 
	$ip_count = 1 << (32 - $rcidr);

	echo "<H2>Ban IP Range</H2>";
	echo $ipRange." : IP Range<br><br>";
	echo $iplo." : Network Address<br>";
	echo $iphi." : Broadcast Address<br>";
	echo $ip_count." : Number of IPs in range<br><br>";
	echo "Begin Update:<br><br>";


	if (empty($ipRange)){
		echo "Error: IP range empty. Please see administrator.<br><br>";
	} else {

		$start = ip2long($iplo);
		for ($i = 0; $i < $ip_count; $i++) {

			$ip = long2ip($start + $i);
			
			$sql_existing = $pdo->prepare("
				SELECT 
					id, 
					ipaddress, 
					flag 
				FROM hm_fwban 
				WHERE ".DBIpStringToIntField('ipaddress')." = ".DBIpStringToIntValue($ip)."
			");
			$sql_existing->execute();
			while($row = $sql_existing->fetch(PDO::FETCH_ASSOC)){

				$ipaddressdb = $row['ipaddress'];
				$flag = $row['flag'];
				$id = $row['id'];
			}
			if (empty($ipaddressdb)){
				$sql_new_cidr_ban = $pdo->exec("
					INSERT INTO hm_fwban (timestamp,ipaddress,ban_reason,country) VALUES (".DBGetCurrentDateTime().",'".$ip."','Manual','".$country."')"
				);
				echo "IP ".$ip." added to ban list for firewall rule insertion<br>";
			} else {
				if (($flag==1)||($flag==2)){
					$sql_update_manban = $pdo->exec("
						UPDATE hm_fwban SET flag=3 WHERE id=".$id
					);
					echo "IP ".$ip." previously released - updating and added to list for firewall rule insertion<br>";
				} else if (($flag==5)||($flag==6)){
					echo "IP ".$ip." marked SAFE - no action taken<br>";
				} else {
					echo "IP ".$ip." already banned - no action necessary<br>";
				}
			}
			$ipaddressdb = "";
			$flag = "";
			$id = "";
			$country = "";
		}
	}
?>
</div>

<?php include("foot.php") ?>