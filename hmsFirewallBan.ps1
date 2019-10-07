# ~~~~~~~ BEGIN USER VARIABLES ~~~~~~~
$LANSubnet = '192.168.99' # <-- 3 octets only, please
$MailPorts = '25|465|587|110|995|143|993' # <-- add custom ports if in use
$MySQLAdminUserName = 'root'
$MySQLAdminPassword = 'supersecretpassword'
$MySQLDatabase = 'hmailserver'
$MySQLHost = 'localhost'
$DBErrorLog = 'C:\scripts\hmailserver\FWBan\DBError.log'
$FirewallLog = 'C:\scripts\hmailserver\FWBan\Firewall\pfirewall.log'
# ~~~~~~~ END USER VARIABLES ~~~~~~~

Function MySQLQuery($Query) {
	$ConnectionString = "server=" + $MySQLHost + ";port=3306;uid=" + $MySQLAdminUserName + ";pwd=" + $MySQLAdminPassword + ";database=" + $MySQLDatabase
	Try {
	  [void][System.Reflection.Assembly]::LoadWithPartialName("MySql.Data")
	  $Connection = New-Object MySql.Data.MySqlClient.MySqlConnection
	  $Connection.ConnectionString = $ConnectionString
	  $Connection.Open()
	  $Command = New-Object MySql.Data.MySqlClient.MySqlCommand($Query, $Connection)
	  $DataAdapter = New-Object MySql.Data.MySqlClient.MySqlDataAdapter($Command)
	  $DataSet = New-Object System.Data.DataSet
	  $RecordCount = $dataAdapter.Fill($dataSet, "data")
	  $DataSet.Tables[0]
	  }
	Catch {
	  Write-Output "$((get-date).ToString(`"yy/MM/dd HH:mm:ss.ff`")) : ERROR : Unable to run query : $query `n$Error[0]" | out-file $DBErrorLog -append
	 }
	Finally {
	  $Connection.Close()
	  }
}

#	Check to see if hMailServer is running. If not, quit. MySQL is a dependency of hMailServer service so you're actually checking both.
#	Prevents scheduled task failures at bootup.
If ((get-service hMailServer).Status -ne 'Running'){exit}

#	Look for new entries and add them to firewall
#	First delete any duplicate IP entries in the database since the last run
$Query = "DELETE t1 FROM hm_fwban t1, hm_fwban t2 WHERE t1.id > t2.id AND t1.ipaddress = t2.ipaddress AND t1.timestamp >= now() - interval 5 minute"
MySQLQuery $Query
#	Now find all new (non-duplicated) IP entries
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE timestamp >= now() - interval 5 minute"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	#	Check each against previous entries marked safe
	$Query = "SELECT flag FROM hm_fwban WHERE ipaddress='$IP' AND timestamp < now() - interval 5 minute"
	MySQLQuery $Query | foreach {
		$FlagSafe = $_.flag
	}
	#	If newly marked safe, delete firewall rule and update flag to safe
	If ($FlagSafe -match 5){
		& netsh advfirewall firewall delete rule name=`"$IP`"
		$Query = "UPDATE hm_fwban SET flag = 6 WHERE id='$ID'"	
		MySQLQuery $Query
	}
	#	If previously marked safe (firewall rule already removed), update flag to safe
	ElseIf ($FlagSafe -match 6){
		$Query = "UPDATE hm_fwban SET flag = 6 WHERE id='$ID'"	
		MySQLQuery $Query
	}
	#	All others (not marked safe) add firewall rule and update flag
	Else {
		& netsh advfirewall firewall add rule name="$IP" description="Rule added $((get-date).ToString('MM/dd/yy'))" dir=in interface=any action=block remoteip=$IP
		$Query = "UPDATE hm_fwban SET flag = NULL WHERE id='$ID'"
		MySQLQuery $Query
	}
}

#	Pick up any missed NEW entries (out of interval)
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE flag=4"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	& netsh advfirewall firewall add rule name="$IP" description="Rule added $((get-date).ToString('MM/dd/yy'))" dir=in interface=any action=block remoteip=$IP
	$Query = "UPDATE hm_fwban SET flag = NULL WHERE id='$ID'"
	MySQLQuery $Query
}

#	Pickup entries marked SAFE through webadmin
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE flag=5"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	& netsh advfirewall firewall delete rule name=`"$IP`"
	$Query = "UPDATE hm_fwban SET flag=6 WHERE id='$ID'"
	MySQLQuery $Query
}

#	Pickup entries marked for RELEASE through webadmin
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE flag=2"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	& netsh advfirewall firewall delete rule name=`"$IP`"
	$Query = "UPDATE hm_fwban SET flag=1 WHERE id='$ID'"
	MySQLQuery $Query
}

#	Pickup entries marked for REBAN or UNSAFE through webadmin
#	First delete any duplicate IP entries to be rebanned to prevent duplicate firewall rules
$Query = "DELETE t1 FROM hm_fwban t1, hm_fwban t2 WHERE t1.id > t2.id AND t1.ipaddress = t2.ipaddress AND (t1.flag=3 OR t1.flag=7)"
MySQLQuery $Query
#	Now find all new (non-duplicated) IP entries and add firewall rule
$Query = "SELECT DISTINCT(ipaddress), id FROM hm_fwban WHERE flag=3 OR flag=7"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	& netsh advfirewall firewall add rule name="$IP" description="Rule added $((get-date).ToString('MM/dd/yy')) - REBAN" dir=in interface=any action=block remoteip=$IP
	$Query = "UPDATE hm_fwban SET flag = NULL WHERE id='$ID'"
	MySQLQuery $Query
}

#	Pickup entries from IDS 
$Query = "SELECT ipaddress, country, helo FROM hm_ids WHERE hits > 2"
MySQLQuery $Query | foreach {
	$IP = $_.ipaddress
	$Country = $_.country
	$HELO = $_.helo
	& netsh advfirewall firewall add rule name="$IP" description="Rule added $((get-date).ToString('MM/dd/yy')) - IDS" dir=in interface=any action=block remoteip=$IP
	# Insert IP record into firewall ban table
	$Query = "INSERT INTO hm_fwban (timestamp,ipaddress,ban_reason,country,flag,helo) VALUES (NOW(),'$IP','IDS','$Country','NULL','$HELO');"
	MySQLQuery $Query
	# Delete IP from IDS
	$Query = "DELETE FROM hm_ids WHERE ipaddress = '$IP'"
	MySQLQuery $Query
}

#	Delete IDS entries that are already banned
$Query = "SELECT ipaddress FROM hm_fwban"
MySQLQuery $Query | foreach {
	$IP = $_.ipaddress
	$Query = "DELETE FROM hm_ids WHERE ipaddress = '$IP'"
	MySQLQuery $Query
}

#	Get firewall logs - https://github.com/zarabelin/Get-WindowsFirewallLogs/blob/master/Get-WindowsFirewallLog.ps1
$LSRegex = "$LANSubnet\.\d{1,3}"
$FirewallLog = "C:\scripts\hmailserver\FWBan\Firewall\pfirewall.log"
$MinuteSpan = 5 # Should match interval of scheduled task
$EndTime = (get-date).ToString("HH:mm:ss")
$StartTime = ((get-date) - (New-TimeSpan -Minutes $MinuteSpan)).ToString("HH:mm:ss")
$DateEnd = (get-date).ToString("yyyy-MM-dd")
$DateStart = ((get-date) - (New-TimeSpan -Minutes $MinuteSpan)).ToString("yyyy-MM-dd")

$FirewallLogObjects = import-csv -Path $FirewallLog -Delimiter " " -Header Date, Time, Action, Protocol, SourceIP, `
    DestinationIP, SourcePort, DestinationPort, Size, tcpflags, tcpsyn, tcpack, tcpwin, icmptype, icmpcode, info, path | `
    Where-Object {$_.date -match "[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]"}
$FirewallLogObjects = $FirewallLogObjects | Where-Object {$_.Date -ge $DateStart -and $_.Date -le $DateEnd}
$FirewallLogObjects = $FirewallLogObjects | Where-Object {$_.Time -ge $StartTime -and $_.Time -le $EndTime}

$FirewallLogObjects | foreach-object {
	if ($_.DestinationPort -match $MailPorts) {
		if ($_.SourceIP -notmatch $LSRegex){
			$IP = ($_.SourceIP).trim()
			$DateTime = (($_.Date).trim()+" "+($_.Time).trim())
			$Query = "INSERT INTO hm_fwban_rh (timestamp, ipaddress) VALUES ('$DateTime', '$IP')"
			MySQLQuery $Query
		}
	}
}

#######################################
#                                     #
#       EXAMPLE AUTO EXPIRATION       #
#  Comment out or delete if unwanted  #
#                                     #
#######################################

#	EXAMPLE AUTO EXPIRE! - Automatic expiration from firewall - Reason: Spamhaus
$Ban_Reason = "Spamhaus" 	#<-- Needs to match a ban_reason you selected as trigger
$Days = "30" 				#<-- Days until expires
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE timestamp < now() - interval $Days day AND ban_reason LIKE '$Ban_Reason' AND flag IS NULL"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	& netsh advfirewall firewall delete rule name=`"$IP`"
	$Query = "UPDATE hm_fwban SET flag=1 WHERE id='$ID'"
	MySQLQuery $Query
}

#	EXAMPLE AUTO EXPIRE! - Automatic expiration from firewall - Country: Hungary
$Country = "Hungary" 		#<-- Country name (check spelling!)
$Days = "10" 				#<-- Days until expires
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE timestamp < now() - interval $Days day AND country LIKE '$Country' AND flag IS NULL"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	& netsh advfirewall firewall delete rule name=`"$IP`"
	$Query = "UPDATE hm_fwban SET flag=1 WHERE id='$ID'"
	MySQLQuery $Query
}

#	EXAMPLE AUTO EXPIRE! - Automatic expiration from firewall - All IPs
$Days = "365" 				#<-- Days until expires
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE timestamp < now() - interval $Days day AND flag IS NULL"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	& netsh advfirewall firewall delete rule name=`"$IP`"
	$Query = "UPDATE hm_fwban SET flag=1 WHERE id='$ID'"
	MySQLQuery $Query
}