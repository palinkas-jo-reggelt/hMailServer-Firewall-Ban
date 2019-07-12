Function MySQLQuery($Query) {
	$MySQLAdminUserName = 'root'
	$MySQLAdminPassword = 'supersecretpassword'
	$MySQLDatabase = 'hmailserver'
	$MySQLHost = 'localhost'
	$ConnectionString = "server=" + $MySQLHost + ";port=3306;uid=" + $MySQLAdminUserName + ";pwd=" + $MySQLAdminPassword + ";database="+$MySQLDatabase
	Try {
	  [void][System.Reflection.Assembly]::LoadWithPartialName("MySql.Data")
	  $Connection = New-Object MySql.Data.MySqlClient.MySqlConnection
	  $Connection.ConnectionString = $ConnectionString
	  $Connection.Open()
	  $Command = New-Object MySql.Data.MySqlClient.MySqlCommand($Query, $Connection)
	  $DataAdapter = New-Object MySql.Data.MySqlClient.MySqlDataAdapter($Command)
	  $DataSet = New-Object System.Data.DataSet
	  $RecordCount = $dataAdapter.Fill($dataSet, "data")
	  $DataSet.Tables[0] | Out-File C:\scripts\hmailserver\FWBan\IP.txt
	  }
	Catch {
	  Write-Host "ERROR : Unable to run query : $query `n$Error[0]"
	 }
	Finally {
	  $Connection.Close()
	  }
}

Function MySQLQueryUpdate($Query) {
	$MySQLAdminUserName = 'root'
	$MySQLAdminPassword = 'supersecretpassword'
	$MySQLDatabase = 'hmailserver'
	$MySQLHost = 'localhost'
	$ConnectionString = "server=" + $MySQLHost + ";port=3306;uid=" + $MySQLAdminUserName + ";pwd=" + $MySQLAdminPassword + ";database="+$MySQLDatabase
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
	  Write-Host "ERROR : Unable to run query : $query `n$Error[0]"
	 }
	Finally {
	  $Connection.Close()
	  }
}

#	Look for new entries and add them to firewall
#	First delete any duplicate IP entries in the database since the last run
$Query = "DELETE t1 FROM hm_fwban t1, hm_fwban t2 WHERE t1.id > t2.id AND t1.ipaddress = t2.ipaddress AND t1.timestamp >= now() - interval 5 minute"
MySQLQueryUpdate $Query
#	Now find all new (non-duplicated) IP entries and add firewall rule
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE timestamp >= now() - interval 5 minute"
MySQLQuery $Query
$timestamp = Get-Date -format 'yy/MM/dd HH:mm'
$regexIP = '([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})'
$regexID = '(\s{0,}[0-9]+\s{0,}$)'
$IPList = Get-Content C:\scripts\hmailserver\FWBan\IP.txt
foreach ($IPAddress in $IPList) {
	if ($IPAddress -match $regexIP){
		$IP = [regex]::matches($IPAddress, $regexIP)
		& netsh advfirewall firewall add rule name="$IP" description="Rule added $timestamp" dir=in interface=any action=block remoteip=$IP
		$ID = (([regex]::matches($IPAddress, $regexID)) -replace '\s','')
		$Query = "UPDATE hm_fwban SET flag=NULL WHERE id='$ID'"
		MySQLQueryUpdate $Query
	}
}

#	Pickup entries marked for release through webadmin
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE flag=2"
MySQLQuery $Query
$regexIP = '([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})'
$regexID = '(\s{0,}[0-9]+\s{0,}$)'
$IPList = Get-Content C:\scripts\hmailserver\FWBan\IP.txt
foreach ($IPAddress in $IPList) {
	if ($IPAddress -match $regexIP){
		$IP = [regex]::matches($IPAddress, $regexIP)
		& netsh advfirewall firewall delete rule name=`"$IP`"
		$ID = (([regex]::matches($IPAddress, $regexID)) -replace '\s','')
		$Query = "UPDATE hm_fwban SET flag=1 WHERE id='$ID'"
		MySQLQueryUpdate $Query
	}
}

#	Pickup entries marked for REBAN through webadmin
#	First delete any duplicate IP entries to be rebanned to prevent duplicate firewall rules
$Query = "DELETE t1 FROM hm_fwban t1, hm_fwban t2 WHERE t1.id > t2.id AND t1.ipaddress = t2.ipaddress AND t1.flag=3"
MySQLQueryUpdate $Query
#	Now find all new (non-duplicated) IP entries and add firewall rule
$Query = "SELECT DISTINCT(ipaddress), id FROM hm_fwban WHERE flag=3"
MySQLQuery $Query
$regexIP = '([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})'
$regexID = '(\s{0,}[0-9]+\s{0,}$)'
$IPList = Get-Content C:\scripts\hmailserver\FWBan\IP.txt
foreach ($IPAddress in $IPList) {
	if ($IPAddress -match $regexIP){
		$IP = [regex]::matches($IPAddress, $regexIP)
		& netsh advfirewall firewall add rule name="$IP" description="Rule added $timestamp - REBAN" dir=in interface=any action=block remoteip=$IP
		$ID = (([regex]::matches($IPAddress, $regexID)) -replace '\s','')
		$Query = "UPDATE hm_fwban SET flag=NULL WHERE id='$ID'"
		MySQLQueryUpdate $Query
	}
}

#	EXAMPLE AUTO EXPIRE! - Automatic expiration from firewall - Reason: Spamhaus
$Ban_Reason = "Spamhaus" 	#<-- Needs to match a ban_reason you selected as trigger
$Days = "30" 				#<-- Days until expires
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE timestamp < now() - interval $Days day AND ban_reason LIKE '$Ban_Reason' AND flag IS NULL"
MySQLQuery $Query
$regexIP = '([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})'
$regexID = '(\s{0,}[0-9]+\s{0,}$)'
$IPList = Get-Content C:\scripts\hmailserver\FWBan\IP.txt
foreach ($IPAddress in $IPList) {
	if ($IPAddress -match $regexIP){
		$IP = [regex]::matches($IPAddress, $regexIP)
		& netsh advfirewall firewall delete rule name=`"$IP`"
		$ID = (([regex]::matches($IPAddress, $regexID)) -replace '\s','')
		$Query = "UPDATE hm_fwban SET flag=1 WHERE id='$ID'"
		MySQLQueryUpdate $Query
	}
}

#	EXAMPLE AUTO EXPIRE! - Automatic expiration from firewall - Country: Hungary
$Country = "Hungary" 		#<-- Country name (check spelling!)
$Days = "10" 				#<-- Days until expires
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE timestamp < now() - interval $Days day AND country LIKE '$Country' AND flag IS NULL"
MySQLQuery $Query
$regexIP = '([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})'
$regexID = '(\s{0,}[0-9]+\s{0,}$)'
$IPList = Get-Content C:\scripts\hmailserver\FWBan\IP.txt
foreach ($IPAddress in $IPList) {
	if ($IPAddress -match $regexIP){
		$IP = [regex]::matches($IPAddress, $regexIP)
		& netsh advfirewall firewall delete rule name=`"$IP`"
		$ID = (([regex]::matches($IPAddress, $regexID)) -replace '\s','')
		$Query = "UPDATE hm_fwban SET flag=1 WHERE id='$ID'"
		MySQLQueryUpdate $Query
	}
}

#	EXAMPLE AUTO EXPIRE! - Automatic expiration from firewall - All IPs
$Days = "365" 				#<-- Days until expires
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE timestamp < now() - interval $Days day AND flag IS NULL"
MySQLQuery $Query
$regexIP = '([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})'
$regexID = '(\s{0,}[0-9]+\s{0,}$)'
$IPList = Get-Content C:\scripts\hmailserver\FWBan\IP.txt
foreach ($IPAddress in $IPList) {
	if ($IPAddress -match $regexIP){
		$IP = [regex]::matches($IPAddress, $regexIP)
		& netsh advfirewall firewall delete rule name=`"$IP`"
		$ID = (([regex]::matches($IPAddress, $regexID)) -replace '\s','')
		$Query = "UPDATE hm_fwban SET flag=1 WHERE id='$ID'"
		MySQLQueryUpdate $Query
	}
}

#	Get firewall logs - https://github.com/zarabelin/Get-WindowsFirewallLogs/blob/master/Get-WindowsFirewallLog.ps1
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
	if ($_.DestinationPort -match "25|465|587|110|993|143|995") {
		if ($_.SourceIP -notmatch "192.168.99.1"){
			$IP = ($_.SourceIP).trim()
			$DateTime = (($_.Date).trim()+" "+($_.Time).trim())
			$Query = "INSERT INTO hm_fwban_rh (timestamp, ipaddress) VALUES ('$DateTime', '$IP')"
			MySQLQueryUpdate $Query
		}
	}
}

