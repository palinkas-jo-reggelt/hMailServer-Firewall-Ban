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
	Create scheduled task to run every 5 minutes

	Flag Logic:
	
	Flag	Meaning
	====	=======
	NULL	Has been added as a firewall rule
	1   	Has been released from firewall (firewall rule deleted)
	2   	Marked for release by manual release (release.php) but not firewall rule not yet deleted - after firewall rule deleted, reset flag to 1
	3   	Marked for reban by manual reban (reban.php) but firewall rule not yet added - after firewall rule added, reset flag to NULL
	4   	Default entry - signifies IP has been added to database but firewall rule has not yet been added - after firewall rule added, reset flag to NULL
	5   	Marked SAFE in webadmin but firewall rule not yet deleted - permanently removes firewall rule and prevents future bans
	6   	Marked SAFE and firewal rule deleted
	7   	Marked for removal from SAFE list and firewall rule added

.EXAMPLE

#>

#######################################
#                                     #
#      INCLUDE REQUIRED FILES         #
#                                     #
#######################################

# region Include required files
#
$ScriptDirectory = Split-Path -Path $MyInvocation.MyCommand.Definition -Parent
try {
	.("$ScriptDirectory\CommonCode.ps1")
}
catch {
	Write-Host "Error while loading supporting PowerShell Scripts" 
}
#endregion

#######################################
#                                     #
#              STARTUP                #
#                                     #
#######################################

#	Load User Variables
$ini = Parse-IniFile("$PSScriptRoot\Config.INI")


#######################################
#                                     #
#             FUNCTIONS               #
#                                     #
#######################################

Function RemRuleIP($IP) {
	$Query = "SELECT rulename FROM hm_fwban WHERE ipaddress = '$IP'"
	RunSQLQuery $Query | ForEach {
		$RuleName = $_.rulename
	}

	If (-not($RuleName)) {
		& netsh advfirewall firewall delete rule name=`"$IP`"
	}
 Else {
		$RuleList = "$PSScriptRoot\fwrulelist.txt"
		$NewLine = [System.Environment]::NewLine

		Get-NetshFireWallrule ("$RuleName") | ForEach {
			$RemoteIP = $_.RemoteIP
			$ReplaceCIDR = ($RemoteIP).Replace("/32", "")
			$ReplaceNL = ($ReplaceCIDR).Replace(",", $NewLine)
			Write-Output $ReplaceNL 
		} | out-file $RuleList

		Get-Content $RuleList | where { $_ -ne $IP } | Out-File "$RuleList.delIP.txt"
		$NL = [System.Environment]::NewLine
		$Content = [String] $Template = [System.IO.File]::ReadAllText("$RuleList.delIP.txt")
		$Content.Replace($NL, ",") | Out-File "$RuleList.rule.txt"
		(Get-Content -Path "$RuleList.rule.txt") -Replace ',$', '' | Set-Content -Path "$RuleList.rule.txt"

		& netsh advfirewall firewall delete rule name=`"$RuleName`"
		& netsh advfirewall firewall add rule name=`"$RuleName`" description="FWB Rules for $DateIP" dir=in interface=any action=block remoteip=$(Get-Content "$RuleList.rule.txt")
	}
}


#######################################
#                                     #
#          DATABASE SCRIPTS           #
#                                     #
#######################################

#	Check to see if hMailServer is running. If not, quit. MySQL is a dependency of hMailServer service so you're actually checking both.
#	Prevents scheduled task failures at bootup.
If ((get-service hMailServer).Status -ne 'Running') { exit }

$Query = ""

If (IsMSSQL) {
	#	Create hm_fwban table if it doesn't exist
	$Query = "
		IF NOT EXISTS (SELECT 1 FROM SYSOBJECTS WHERE NAME = 'hm_fwban')
		BEGIN
			CREATE TABLE hm_fwban (
				ID int IDENTITY(1,1) NOT NULL PRIMARY KEY,
				ipaddress varchar NOT NULL,
				timestamp datetime NOT NULL,
				ban_reason varchar(192) DEFAULT NULL,
				country varchar(192) DEFAULT NULL,
				flag int DEFAULT NULL,
				helo varchar(192) DEFAULT NULL,
				ptr varchar(192) DEFAULT NULL,
				rulename varchar(192) DEFAULT NULL
			)
		END;
		"
	RunSQLQuery $Query
	#	Create hm_fwban_rh table if it doesn't exist
	$Query = "
		IF NOT EXISTS (SELECT 1 FROM SYSOBJECTS WHERE NAME = 'hm_fwban_rh')
		BEGIN
			CREATE TABLE hm_fwban_rh (
				id int IDENTITY(1,1) NOT NULL PRIMARY KEY,
				timestamp datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				ipaddress varchar(15) NOT NULL
			)
		END;
		"
	RunSQLQuery $Query
	#	Create hm_ids table if it doesn't exist
	$Query = "
	IF NOT EXISTS (SELECT 1 FROM SYSOBJECTS WHERE NAME = 'hm_ids')
	BEGIN
		CREATE TABLE hm_ids (
			timestamp datetime NOT NULL,
			ipaddress varchar(15) NOT NULL PRIMARY KEY,
			hits int NOT NULL,
			country varchar(64) DEFAULT NULL,
			helo varchar(128) DEFAULT NULL
		)
	END;
	"
	RunSQLQuery $Query
}
elseif (ISMySQL) {
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
		ptr varchar(192) DEFAULT NULL,
		rulename varchar(192) DEFAULT NULL,
		PRIMARY KEY (ID),
		UNIQUE KEY ID (ID)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		COMMIT;
		"
	RunSQLQuery $Query
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
	RunSQLQuery $Query
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
	RunSQLQuery $Query
}




#######################################
#                                     #
#       FIREWALL RULES SCRIPTS        #
#                                     #
#######################################

#	Establish scheduled task interval 
$Interval = $ini['Interval']['TaskInterval']

#	Set time so interval queries align
$QueryTime = (get-date).ToString("yyyy-MM-dd HH:mm:00")

#	Pickup entries marked SAFE through webadmin
$Query = "SELECT ipaddress, id, $(DBCastDateTimeFieldAsDate("timestamp")) AS dateip FROM hm_fwban WHERE flag=5"
RunSQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	$DateIP = (Get-Date -date $_.dateip)
	RemRuleIP $IP
	$Query = "UPDATE hm_fwban SET flag=6, rulename=NULL WHERE id='$ID'"
	RunSQLQuery $Query
}

#	Pickup entries marked for RELEASE through webadmin
$Query = "SELECT ipaddress, id, $(DBCastDateTimeFieldAsDate("timestamp")) AS dateip FROM hm_fwban WHERE flag=2"
RunSQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	$DateIP = (Get-Date -date $_.dateip)
	RemRuleIP $IP
	$Query = "UPDATE hm_fwban SET flag=1, rulename=NULL WHERE id='$ID'"
	RunSQLQuery $Query
}

#	Look for new entries and add them to firewall
#	First delete any duplicate IP entries in the database since the last run
$Query = "DELETE t1 FROM hm_fwban t1, hm_fwban t2 WHERE t1.id > t2.id AND t1.ipaddress = t2.ipaddress AND t1.timestamp >= " + $(DBSubtractIntervalFromDate $QueryTime "minute"  $Interval)

RunSQLQuery $Query
#	Now find all new (non-duplicated) IP entries
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE flag = 4 AND timestamp >= " + $(DBSubtractIntervalFromDate $QueryTime "minute"  $Interval)
RunSQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	#	Check each against previous entries marked safe
	$Query = "SELECT flag FROM hm_fwban WHERE ipaddress = '$IP' AND timestamp < " + $(DBSubtractIntervalFromDate $QueryTime "minute" $Interval)
	RunSQLQuery $Query | foreach {
		$FlagSafe = $_.flag
	}
	#	If newly marked safe, delete firewall rule and update flag to safe
	If ($FlagSafe -match 5) {
		RemRuleIP $IP
		$Query = "UPDATE hm_fwban SET flag=6, rulename=NULL WHERE id='$ID'"	
		RunSQLQuery $Query
	}
	#	If previously marked safe (firewall rule already removed), update flag to safe
	ElseIf ($FlagSafe -match 6) {
		$Query = "UPDATE hm_fwban SET flag = 6 WHERE id='$ID'"	
		RunSQLQuery $Query
	}
	#	All others (not marked safe) add firewall rule and update flag
	Else {
		& netsh advfirewall firewall add rule name="$IP" description="Rule added $((get-date).ToString('MM/dd/yy'))" dir=in interface=any action=block remoteip=$IP
		$Query = "UPDATE hm_fwban SET flag=NULL, rulename='$IP' WHERE id='$ID'"
		RunSQLQuery $Query
	}
}

#	Pick up any missed NEW entries (out of interval)
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE flag=4"
RunSQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	& netsh advfirewall firewall add rule name="$IP" description="Rule added $((get-date).ToString('MM/dd/yy'))" dir=in interface=any action=block remoteip=$IP
	$Query = "UPDATE hm_fwban SET flag=NULL, rulename='$IP' WHERE id='$ID'"
	RunSQLQuery $Query
}

#	Pickup entries marked for REBAN or UNSAFE through webadmin
#	First delete any duplicate IP entries to be rebanned to prevent duplicate firewall rules
$Query = "DELETE t1 FROM hm_fwban t1, hm_fwban t2 WHERE t1.id > t2.id AND t1.ipaddress = t2.ipaddress AND (t1.flag=3 OR t1.flag=7)"
RunSQLQuery $Query
#	Now find all new (non-duplicated) IP entries and add firewall rule
$Query = "SELECT DISTINCT(ipaddress), id, $(DBCastDateTimeFieldAsDate("timestamp")) AS dateip FROM hm_fwban WHERE flag=3 OR flag=7"
RunSQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	$DateIP = (Get-Date -date $_.dateip)
	& netsh advfirewall firewall add rule name="$IP" description="Rule added $((get-date).ToString('MM/dd/yy')) - REBAN" dir=in interface=any action=block remoteip=$IP
	$Query = "UPDATE hm_fwban SET flag=NULL, rulename='$IP' WHERE id='$ID'"
	RunSQLQuery $Query
}

#######################################
#                                     #
#                IDS                  #
#    (Intrusion Detection System)     #
#                                     #
#######################################

#	Pickup entries from IDS 
$Query = "SELECT ipaddress, country FROM hm_ids WHERE hits > 2"
RunSQLQuery $Query | foreach {
	$TS = $_.timestamp
	$IP = $_.ipaddress
	$Country = $_.country

	#some IPs don't have PTR record
	try {
		$PTR = [System.Net.Dns]::GetHostEntry($IP).HostName
	}
	catch {
		$PTR = "No.PTR.Record"
	}
	
	& netsh advfirewall firewall add rule name="$IP" description="Rule added $((get-date).ToString('MM/dd/yy')) - IDS" dir=in interface=any action=block remoteip=$IP
	# Insert IP record into firewall ban table
	$Query = "INSERT INTO hm_fwban (timestamp,ipaddress,ban_reason,country,flag,ptr,rulename) VALUES ($(DBGetCurrentDateTime),'$IP','IDS','$Country',NULL,'$PTR','$IP');"
	RunSQLQuery $Query
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
			GROUP BY ipaddress, country
	) AS a
	INNER JOIN
	(
		SELECT ipaddress AS fwbip
			FROM hm_fwban 
			WHERE flag IS NULL OR flag='3' OR flag='4' OR flag='7'
			GROUP BY ipaddress
	) AS b
	ON a.idsip = b.fwbip
	ORDER BY b.fwbip
"
RunSQLQuery $Query | foreach {
	$IP = $_.idsip
	$Query = "DELETE FROM hm_ids WHERE ipaddress = '$IP'"
	RunSQLQuery $Query
}

#	Expire old IDS entries 

$Query = "DELETE FROM hm_ids WHERE timestamp < " + $(DBSubtractIntervalFromField $(DBGetCurrentDateTime)  "hour"  $($ini['Interval']['IDSExpire']))
RunSQLQuery $Query

#######################################
#                                     #
#        FIREWALL LOG PARSING         #
#                                     #
#######################################

<#	Get firewall logs - https://github.com/zarabelin/Get-WindowsFirewallLogs/blob/master/Get-WindowsFirewallLog.ps1  #>
$LSRegex = "$($ini['Firewall']['LANSubnet'])\.\d{1,3}"
write-host $LSRegex
$MailPorts = $ini['Firewall']['MailPorts']
$FirewallLog = $ini['Firewall']['FirewallLog']
$EndTime = $QueryTime
$StartTime = ([datetime]::parseexact($QueryTime, 'yyyy-MM-dd HH:mm:00', $Null ) - (New-TimeSpan -Minutes $Interval)).ToString("HH:mm:ss")
$DateEnd = $QueryTime
$DateStart = ([datetime]::parseexact($QueryTime, 'yyyy-MM-dd HH:mm:00', $Null ) - (New-TimeSpan -Minutes $Interval)).ToString("yyyy-MM-dd")

$FirewallLogObjects = import-csv -Path $FirewallLog -Delimiter " " -Header Date, Time, Action, Protocol, SourceIP, `
	DestinationIP, SourcePort, DestinationPort, Size, tcpflags, tcpsyn, tcpack, tcpwin, icmptype, icmpcode, info, path | `
	Where-Object { $_.Date -match "[0-9]{4}-[0-9]{2}-[0-9]{2}" }
$FirewallLogObjects = $FirewallLogObjects | Where-Object { $_.Date -ge $DateStart -and $_.Date -lt $DateEnd }
$FirewallLogObjects = $FirewallLogObjects | Where-Object { $_.Time -ge $StartTime -and $_.Time -lt $EndTime }

$FirewallLogObjects | foreach-object {
	If (($_.Action -match 'DROP') -and ($_.DestinationPort -match $MailPorts) -and ($_.SourceIP -notmatch $LSRegex)) {
		$IP = $_.SourceIP
		$DateTime = $_.Date + " " + $_.Time
		$Query = "INSERT INTO hm_fwban_rh (timestamp, ipaddress) VALUES ('$DateTime', '$IP')"
		RunSQLQuery $Query
	}
}

#######################################
#                                     #
#      EXAMPLE AUTO EXPIRATION        #
#       (Uncomment if wanted)         #
#                                     #
#######################################

<#	EXAMPLE AUTO EXPIRE - Automatic expiration from firewall - Reason: "One Hit Wonders"  #>
<#	Release all IPs that never returned after specified number of days  #>
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
RunSQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	$DateIP = (Get-Date -date $_.dateip)
	RemRuleIP $IP
	$Query = "UPDATE hm_fwban SET flag=1, rulename=NULL WHERE id='$ID'"
	RunSQLQuery $Query
}
#>

<#	EXAMPLE AUTO EXPIRE - Automatic expiration from firewall - Reason: Spamhaus  #>
<#
$Ban_Reason = "Spamhaus" 	#<-- Needs to match a ban_reason you selected as trigger
$Days = "30" 				#<-- Days until expires
$Query = "SELECT ipaddress, id, DATE(timestamp) AS dateip FROM hm_fwban WHERE timestamp < '$QueryTime' - interval $Days day AND ban_reason LIKE '$Ban_Reason' AND flag IS NULL"
RunSQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	$DateIP = (Get-Date -date $_.dateip)
	RemRuleIP $IP
	$Query = "UPDATE hm_fwban SET flag=1, rulename=NULL WHERE id='$ID'"
	RunSQLQuery $Query
}
#>

<#	EXAMPLE AUTO EXPIRE - Automatic expiration from firewall - Country: Hungary  #>
<#
$Country = "Hungary" 		#<-- Country name (check spelling!)
$Days = "10" 				#<-- Days until expires
$Query = "SELECT ipaddress, id, DATE(timestamp) AS dateip FROM hm_fwban WHERE timestamp < '$QueryTime' - interval $Days day AND country LIKE '$Country' AND flag IS NULL"
RunSQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	$DateIP = (Get-Date -date $_.dateip)
	RemRuleIP $IP
	$Query = "UPDATE hm_fwban SET flag=1, rulename=NULL WHERE id='$ID'"
	RunSQLQuery $Query
}
#>

<#	EXAMPLE AUTO EXPIRE - Automatic expiration from firewall - All IPs #>
<#
$Days = "60" 				#<-- Days until expires
$Query = "SELECT ipaddress, id, DATE(timestamp) AS dateip FROM hm_fwban WHERE timestamp < '$QueryTime' - interval $Days day AND flag IS NULL"
RunSQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	$DateIP = (Get-Date -date $_.dateip)
	RemRuleIP $IP
	$Query = "UPDATE hm_fwban SET flag=1, rulename=NULL WHERE id='$ID'"
	RunSQLQuery $Query
}
#>