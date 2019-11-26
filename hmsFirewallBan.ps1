<#
_  _ _  _  _  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /_\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/   \| |___ ___] |___ |  \  \/  |___ |  \     
____ _ ____ ____ _ _ _  _  _    _       ___   _  _  _ 
|___ | |__/ |___ | | | /_\ |    |       |__] /_\ |\ | 
|    | |  \ |___ |_|_|/   \|___ |___    |__]/   \| \| 

.SYNOPSIS
	Powershell component to hMailServer Firewall Ban (hmsFirewallBan.ps1)

.DESCRIPTION
	Backend firewall rule administration for hMailServer Firewall Ban Project

.FUNCTIONALITY
	* Reads Firewall Ban database (hm_fwban) and creates firewall rule
	* Works directly with PHP front end
	* Prevents duplicates and removes duplicates automatically
	* Reads firewall log for dropped connections
	* Handles rule auto expiration

.NOTES
	* Create scheduled task to run every 5 minutes
	
.EXAMPLE

#>

###   MYSQL VARIABLES   ########################################################
#                                                                              #
$MySQLAdminUserName = 'hmailserver'                                            #
$MySQLAdminPassword = 'supersecretpassword'                                    #
$MySQLDatabase = 'hmailserver'                                                 #
$MySQLHost = '127.0.0.1'                                                       #
#                                                                              #
###   FIREWALL VARIABLES   #####################################################
#                                                                              #
$LANSubnet = '192.168.99' # <-- 3 octets only, please                          #
$MailPorts = '25|465|587|110|995|143|993' # <-- add custom ports if in use     #
$FirewallLog = 'C:\scripts\hmailserver\FWBan\Firewall\pfirewall.log'           #
#                                                                              #
###   INTERVAL VARIABLES   #####################################################
#                                                                              #
$Interval  = 5   # <-- (minutes) must match the frequency of Win Sched Task    #
$IDSExpire = 2   # <-- (days) expire IDS entries that have not resulted in ban #
#                                                                              #
################################################################################

#######################################
#                                     #
#             FUNCTIONS               #
#                                     #
#######################################

Function MySQLQuery($Query) {
	$ConnectionString = "server=" + $MySQLHost + ";port=3306;uid=" + $MySQLAdminUserName + ";pwd=" + $MySQLAdminPassword + ";database=" + $MySQLDatabase
	Try {
	  $Today = (Get-Date).ToString("yyyyMMdd")
	  $DBErrorLog = "$PSScriptRoot\$Today-DBError.log"
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

Function RemRuleIP($IP){
	$Query = "SELECT DATE(timestamp) AS dateip FROM hm_fwban WHERE ipaddress = '$IP' AND flag IS NULL"
	MySQLQuery $Query | ForEach {
		$DateIP = $_.dateip
	}

	$RuleList = "$PSScriptRoot\fwrulelist.txt"
	$RuleDate = "hMS FWBan "+$DateIP.ToString("yyyy-MM-dd")

	Get-NetFirewallRule -DisplayName $RuleDate | Get-NetFirewallAddressFilter | ForEach {
		write-output $_.RemoteAddress
	} | out-file $RuleList

	Get-Content $RuleList | where { $_ -ne $IP } | Out-File "$RuleList.delIP.txt"
	$NL = [System.Environment]::NewLine
	$Content=[String] $Template= [System.IO.File]::ReadAllText("$RuleList.delIP.txt")
	$Content.Replace($NL,",") | Out-File "$RuleList.rule.txt"
	(Get-Content -Path "$RuleList.rule.txt") -Replace ',$','' | Set-Content -Path "$RuleList.rule.txt"

	& netsh advfirewall firewall delete rule name=`"$RuleDate`"
	& netsh advfirewall firewall add rule name=`"$RuleDate`" description="FWB Rules for $DateIP" dir=in interface=any action=block remoteip=$(Get-Content "$RuleList.rule.txt")
}

Function AddRuleIP($IP){
	$Query = "SELECT DATE(timestamp) AS dateip FROM hm_fwban WHERE ipaddress = '$IP' AND flag IS NOT NULL"
	MySQLQuery $Query | ForEach {
		$DateIP = $_.dateip
	}

	$RuleList = "$PSScriptRoot\RemoteIPList.txt"
	$RuleDate = "hMS FWBan "+$DateIP.ToString("yyyy-MM-dd")

	Get-NetFirewallRule -DisplayName $RuleDate | Get-NetFirewallAddressFilter | ForEach {
		write-output $_.RemoteAddress
	} | out-file $RuleList

	Add-Content $RuleList $IP
	$NL = [System.Environment]::NewLine
	$Content=[String] $Template= [System.IO.File]::ReadAllText($RuleList)
	$Content.Replace($NL,",") | Out-File "$RuleList.rule.txt"
	(Get-Content -Path "$RuleList.rule.txt") -Replace ',$','' | Set-Content -Path "$RuleList.rule.txt"

	& netsh advfirewall firewall delete rule name=`"$RuleDate`"
	& netsh advfirewall firewall add rule name=`"$RuleDate`" description="FWB Rules for $DateIP" dir=in interface=any action=block remoteip=$(Get-Content "$RuleList.rule.txt")
}

#######################################
#                                     #
#          DATABASE SCRIPTS           #
#                                     #
#######################################

#	Check to see if hMailServer is running. If not, quit. MySQL is a dependency of hMailServer service so you're actually checking both.
#	Prevents scheduled task failures at bootup.
If ((get-service hMailServer).Status -ne 'Running'){exit}

#	Create hm_fwban table if it doesn't exist
$Query = "
	CREATE TABLE IF NOT EXISTS hm_fwban (
	  ID int(11) NOT NULL AUTO_INCREMENT,
	  ipaddress varchar(192) NOT NULL,
	  timestamp datetime NOT NULL,
	  ban_reason varchar(192) DEFAULT NULL,
	  country varchar(192) DEFAULT NULL,
	  flag int(1) DEFAULT NULL,
	  helo varchar(192) DEFAULT NULL,
	  PRIMARY KEY (ID),
	  UNIQUE KEY ID (ID)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	COMMIT;
	"
MySQLQuery($Query)

#	Create hm_fwban_rh table if it doesn't exist
$Query = "
	CREATE TABLE IF NOT EXISTS hm_fwban_rh (
	  id int(12) NOT NULL AUTO_INCREMENT,
	  timestamp datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	  ipaddress varchar(15) NOT NULL,
	  PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	COMMIT;
	"
MySQLQuery($Query)

#	Create hm_ids table if it doesn't exist
$Query = "
	CREATE TABLE IF NOT EXISTS hm_ids (
	  timestamp datetime NOT NULL,
	  ipaddress varchar(15) NOT NULL,
	  hits int(1) NOT NULL,
	  country varchar(64) DEFAULT NULL,
	  helo varchar(128) DEFAULT NULL,
	  PRIMARY KEY (ipaddress),
	  UNIQUE KEY ipaddress (ipaddress)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	COMMIT;
	"
MySQLQuery($Query)

#######################################
#                                     #
#       FIREWALL RULES SCRIPTS        #
#                                     #
#######################################

#	Set time for so interval queries align
$QueryTime = (get-date).ToString("yyyy-MM-dd HH:mm:00")

#	Pickup entries marked SAFE through webadmin
$Query = "SELECT ipaddress, id, DATE(timestamp) AS dateip FROM hm_fwban WHERE flag=5"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	$DateIP = (Get-Date -date $_.dateip)
	If ($DateIP -le ((Get-Date).AddDays(-1))) {
		RemRuleIP $IP
	} Else {
		& netsh advfirewall firewall delete rule name=`"$IP`"
	}
	$Query = "UPDATE hm_fwban SET flag=6 WHERE id='$ID'"
	MySQLQuery $Query
}

#	Pickup entries marked for RELEASE through webadmin
$Query = "SELECT ipaddress, id, DATE(timestamp) AS dateip FROM hm_fwban WHERE flag=2"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	$DateIP = (Get-Date -date $_.dateip)
	If ($DateIP -le ((Get-Date).AddDays(-1))) {
		RemRuleIP $IP
	} Else {
		& netsh advfirewall firewall delete rule name=`"$IP`"
	}
	$Query = "UPDATE hm_fwban SET flag=1 WHERE id='$ID'"
	MySQLQuery $Query
}

#	Look for new entries and add them to firewall
#	First delete any duplicate IP entries in the database since the last run
$Query = "DELETE t1 FROM hm_fwban t1, hm_fwban t2 WHERE t1.id > t2.id AND t1.ipaddress = t2.ipaddress AND t1.timestamp >= '$QueryTime' - interval $Interval minute"
MySQLQuery $Query
#	Now find all new (non-duplicated) IP entries
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE flag = 4 AND timestamp >= '$QueryTime' - interval $Interval minute"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	#	Check each against previous entries marked safe
	$Query = "SELECT flag FROM hm_fwban WHERE ipaddress = '$IP' AND timestamp < '$QueryTime' - interval $Interval minute"
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

#	Pickup entries marked for REBAN or UNSAFE through webadmin
#	First delete any duplicate IP entries to be rebanned to prevent duplicate firewall rules
$Query = "DELETE t1 FROM hm_fwban t1, hm_fwban t2 WHERE t1.id > t2.id AND t1.ipaddress = t2.ipaddress AND (t1.flag=3 OR t1.flag=7)"
MySQLQuery $Query
#	Now find all new (non-duplicated) IP entries and add firewall rule
$Query = "SELECT DISTINCT(ipaddress), id, DATE(timestamp) AS dateip FROM hm_fwban WHERE flag=3 OR flag=7"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	$DateIP = (Get-Date -date $_.dateip)
	If ($DateIP -le ((Get-Date).AddDays(-1))) {
		AddRuleIP $IP
	} Else {
		& netsh advfirewall firewall add rule name="$IP" description="Rule added $((get-date).ToString('MM/dd/yy')) - REBAN" dir=in interface=any action=block remoteip=$IP
	}
	$Query = "UPDATE hm_fwban SET flag = NULL WHERE id='$ID'"
	MySQLQuery $Query
}

#	Pickup entries from IDS 
$Query = "SELECT ipaddress, country FROM hm_ids WHERE hits > 2"
MySQLQuery $Query | foreach {
	$TS = $_.timestamp
	$IP = $_.ipaddress
	$Country = $_.country
	& netsh advfirewall firewall add rule name="$IP" description="Rule added $((get-date).ToString('MM/dd/yy')) - IDS" dir=in interface=any action=block remoteip=$IP
	# Insert IP record into firewall ban table
	$Query = "INSERT INTO hm_fwban (timestamp,ipaddress,ban_reason,country,flag) VALUES (NOW(),'$IP','IDS','$Country',NULL);"
	MySQLQuery $Query
}

#	Delete IDS entries that are already banned
$Query = "
	SELECT 
		a.idsip,
		a.country
	FROM
	(
		SELECT ipaddress AS idsip, country
			FROM hm_ids 
			GROUP BY ipaddress
			ORDER BY ipaddress ASC
	) AS a
	INNER JOIN
	(
		SELECT ipaddress AS fwbip
			FROM hm_fwban 
			WHERE flag IS NULL OR flag='3' OR flag='4' OR flag='7'
			GROUP BY ipaddress
			ORDER BY ipaddress ASC
	) AS b
	ON a.idsip = b.fwbip
	ORDER BY b.fwbip
"
MySQLQuery $Query | foreach {
	$IP = $_.idsip
	$Query = "DELETE FROM hm_ids WHERE ipaddress = '$IP'"
	MySQLQuery $Query
}

#	Expire old IDS entries 
$Query = "DELETE FROM hm_ids WHERE timestamp < now() - interval $IDSExpire day"
MySQLQuery $Query

<#	De-Duplicate Firewall Rules List
$FWRuleList = "$PSScriptRoot\fwrulelist.txt"
$DupList = "$PSScriptRoot\fwduplist.txt"
$RegexRule = '^hMS\sFWBan\s([0-9]{4}\-[0-9]{2}\-[0-9]{2})$'

Get-NetFirewallRule | foreach-object {
	if ($_.DisplayName -match $RegexRule){
	write-output $_.DisplayName
	}
} | out-file $FWRuleList

$a = Get-Content $FWRuleList
$ht = @{}
$a | foreach {$ht["$_"] += 1}
$ht.keys | where {$ht["$_"] -gt 1} | foreach { write-output $_ } | out-file $DupList

Get-Content $DupList | foreach {
	& netsh advfirewall firewall delete rule name=`"$_`"
	& netsh advfirewall firewall add rule name="$_" description="Rule added $((get-date).ToString('MM/dd/yy')) - DUP" dir=in interface=any action=block remoteip=$_
}
#>

#######################################
#                                     #
#        FIREWALL LOG PARSING         #
#                                     #
#######################################

#	Get firewall logs - https://github.com/zarabelin/Get-WindowsFirewallLogs/blob/master/Get-WindowsFirewallLog.ps1
$LSRegex = "($LANSubnet\.\d{1,3})"
$EndTime = $QueryTime
$StartTime = ([datetime]::parseexact($QueryTime, 'yyyy-MM-dd HH:mm:00', $Null ) - (New-TimeSpan -Minutes $Interval)).ToString("HH:mm:ss")
$DateEnd = $QueryTime
$DateStart = ([datetime]::parseexact($QueryTime, 'yyyy-MM-dd HH:mm:00', $Null ) - (New-TimeSpan -Minutes $Interval)).ToString("yyyy-MM-dd")

$FirewallLogObjects = import-csv -Path $FirewallLog -Delimiter " " -Header Date, Time, Action, Protocol, SourceIP, `
    DestinationIP, SourcePort, DestinationPort, Size, tcpflags, tcpsyn, tcpack, tcpwin, icmptype, icmpcode, info, path | `
    Where-Object {$_.date -match "[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]"}
$FirewallLogObjects = $FirewallLogObjects | Where-Object {$_.Date -ge $DateStart -and $_.Date -lt $DateEnd}
$FirewallLogObjects = $FirewallLogObjects | Where-Object {$_.Time -ge $StartTime -and $_.Time -lt $EndTime}

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
#         Uncomment if wanted         #
#                                     #
#######################################

#	EXAMPLE AUTO EXPIRE - Automatic expiration from firewall - Reason: "One Hit Wonders" 
#	Release all IPs that never returned after specified number of days
<# 
$Days = "30" 	# <-- Number of days for automatic expiry                   
$Query = "
	SELECT id, ipaddress, DATE(timestamp) AS dateip
	FROM hm_fwban 
	WHERE hm_fwban.ipaddress NOT IN 
	(
		SELECT ipaddress 
		FROM hm_fwban_rh
	) 
	AND timestamp < NOW() - INTERVAL $Days DAY
	AND flag IS NULL
	ORDER BY timestamp DESC
"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	$DateIP = (Get-Date -date $_.dateip)
	If ($DateIP -le ((Get-Date).AddDays(-1))) {
		RemRuleIP $IP
	} Else {
		& netsh advfirewall firewall delete rule name=`"$IP`"
	}
	$Query = "UPDATE hm_fwban SET flag=1 WHERE id='$ID'"
	MySQLQuery $Query
}
#>

#	EXAMPLE AUTO EXPIRE - Automatic expiration from firewall - Reason: Spamhaus 
<#
$Ban_Reason = "Spamhaus" 	#<-- Needs to match a ban_reason you selected as trigger
$Days = "30" 				#<-- Days until expires
$Query = "SELECT ipaddress, id, DATE(timestamp) AS dateip FROM hm_fwban WHERE timestamp < '$QueryTime' - interval $Days day AND ban_reason LIKE '$Ban_Reason' AND flag IS NULL"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	$DateIP = (Get-Date -date $_.dateip)
	If ($DateIP -le ((Get-Date).AddDays(-1))) {
		RemRuleIP $IP
	} Else {
		& netsh advfirewall firewall delete rule name=`"$IP`"
	}
	$Query = "UPDATE hm_fwban SET flag=1 WHERE id='$ID'"
	MySQLQuery $Query
}
#>

#	EXAMPLE AUTO EXPIRE - Automatic expiration from firewall - Country: Hungary 
<#
$Country = "Hungary" 		#<-- Country name (check spelling!)
$Days = "10" 				#<-- Days until expires
$Query = "SELECT ipaddress, id, DATE(timestamp) AS dateip FROM hm_fwban WHERE timestamp < '$QueryTime' - interval $Days day AND country LIKE '$Country' AND flag IS NULL"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	$DateIP = (Get-Date -date $_.dateip)
	If ($DateIP -le ((Get-Date).AddDays(-1))) {
		RemRuleIP $IP
	} Else {
		& netsh advfirewall firewall delete rule name=`"$IP`"
	}
	$Query = "UPDATE hm_fwban SET flag=1 WHERE id='$ID'"
	MySQLQuery $Query
}
#>

#	EXAMPLE AUTO EXPIRE - Automatic expiration from firewall - All IPs 
<#
$Days = "60" 				#<-- Days until expires
$Query = "SELECT ipaddress, id, DATE(timestamp) AS dateip FROM hm_fwban WHERE timestamp < '$QueryTime' - interval $Days day AND flag IS NULL"
MySQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	$DateIP = (Get-Date -date $_.dateip)
	If ($DateIP -le ((Get-Date).AddDays(-1))) {
		RemRuleIP $IP
	} Else {
		& netsh advfirewall firewall delete rule name=`"$IP`"
	}
	$Query = "UPDATE hm_fwban SET flag=1 WHERE id='$ID'"
	MySQLQuery $Query
}
#>