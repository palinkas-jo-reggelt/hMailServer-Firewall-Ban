<#
_  _ _  _  __  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /__\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/    \| |___ ___] |___ |  \  \/  |___ |  \     
                                                        
____ _ ____ ____ _ _ _  __  _    _       ___   __  _  _ 
|___ | |__/ |___ | | | /__\ |    |       |__] /__\ |\ | 
|    | |  \ |___ |_|_|/    \|___ |___    |__]/    \| \| 

.SYNOPSIS
	Analysis of Blocked IPs (firewall log drops)

.DESCRIPTION
	Counts number of firewall drops for a given number of days

.FUNCTIONALITY
	Run whenever you're curious

.NOTES
	Script runs until there are 0 firewall drops for a given number of days
	
.EXAMPLE

#>

### MySQL Variables #############################
                                                #
$MySQLAdminUserName = 'hmailserver'             #
$MySQLAdminPassword = 'supersecretpassword'     #
$MySQLDatabase      = 'hmailserver'             #
$MySQLHost          = 'localhost'               #
                                                #
#################################################

Function MySQLQuery ($Query) {
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

Write-Output " "
Write-Output " "

Write-Output "_  _ _  _  __  _ _    ____ ____ ____ _  _ ____ ____     "
Write-Output "|__| |\/| /__\ | |    [__  |___ |__/ |  | |___ |__/     "
Write-Output "|  | |  |/    \| |___ ___] |___ |  \  \/  |___ |  \     "
Write-Output "                                                        "
Write-Output "____ _ ____ ____ _ _ _  __  _    _       ___   __  _  _ "
Write-Output "|___ | |__/ |___ | | | /__\ |    |       |__] /__\ |\ | "
Write-Output "|    | |  \ |___ |_|_|/    \|___ |___    |__]/    \| \| "
Write-Output ""
Write-Output ""

Write-Output "Block Count - Count number of drops from firewall log"
Write-Output " "
$StartTime = get-date
Write-Output "    Run : $(($StartTime).ToString(`"yy/MM/dd HH:mm`"))"
Write-Output " "

#	Find oldest database entry and count days.
$Query = "Select MIN(timestamp) AS mints FROM hm_fwban"
MySQLQuery($Query) | ForEach {
	$Oldest = $_.mints
}
$NumDays = (New-TimeSpan $Oldest $(Get-Date)).Days

Write-Output ("{0,7} : Number of days data in database" -f ($NumDays).ToString("#,###"))
Write-Output " "

#	Count number of bans in firewall ban database
$Query = "Select COUNT(ipaddress) AS countip from hm_fwban WHERE flag IS NULL"
MySQLQuery($Query) | ForEach {
	$TotalRules = $_.countip
}
Write-Output ("{0,7} : Total number of firewall rules" -f ($TotalRules).ToString("#,###"))
Write-Output " "

#	Count number of distinct IPs recorded in repeat hit database
$Query = "Select COUNT(DISTINCT(ipaddress)) AS totalreturnips, COUNT(ipaddress) AS totalhits FROM hm_fwban_rh"
MySQLQuery($Query) | ForEach {
	$TotalReturnIPs = $_.totalreturnips
}

#	Subtract distinct IPs in RH database from number of bans in FWB database to derive number of FWBans that never returned
$PercentReturns = ([int]$TotalReturnIPs / [int]$TotalRules).ToString("P")
$NeverBlocked = ([int]$TotalRules - [int]$TotalReturnIPs)
$PercentNever = ([int]$NeverBlocked / [int]$TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs never blocked" -f ($NeverBlocked).ToString("#,###"), $PercentNever)
Write-Output " "

#	Find number of distinct IPs that were blocked for a given number of days and continue until no results are found
$a = 0
Do {
	$Query = "SELECT COUNT(*) AS countips FROM (SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > $a) AS returnhits"
	MySQLQuery($Query) | ForEach {
		$ReturnIPs = $_.countips
	}
	$PercentReturns = ($ReturnIPs / $TotalRules)
	If ($ReturnIPs -lt 1) {
		Write-Output ""
		Write-Output "No more results"
		$TimeElapsed = (New-TimeSpan $StartTime $(get-date))
		If (($TimeElapsed).Minutes -eq 1) {$sm = ""} Else {$sm = "s"}
		If (($TimeElapsed).Seconds -eq 1) {$ss = ""} Else {$ss = "s"}
		Write-Output ("Time Elapsed: {0:%m} minute$sm {0:%s} second$ss" -f $TimeElapsed)
	} Else {
		If ($a -eq 0) {$sd = ""} Else {$sd = "s"}
		Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least $($a + 1) day$sd" -f ($ReturnIPs).ToString("#,###"), $PercentReturns.ToString("P"))
	}
	$a++
} Until ($ReturnIPs -lt 1)

Write-Output " "
Write-Output " "