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
	
.EXAMPLE

#>

###   MYSQL VARIABLES   ####################################################
#                                                                          #
$MySQLAdminUserName = 'hmailserver'                                        #
$MySQLAdminPassword = 'supersecretpassword'                                #
$MySQLDatabase      = 'hmailserver'                                        #
$MySQLHost          = 'localhost'                                          #
#                                                                          #
############################################################################

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
Write-Output "    Run : $((get-date).ToString(`"yy/MM/dd HH:mm`"))"
Write-Output " "

$Query = "Select MIN(timestamp) AS mints FROM hm_fwban"
MySQLQuery($Query) | ForEach {
	$Oldest = $_.mints
}
$NumDays = (New-TimeSpan $Oldest $(Get-Date)).Days

Write-Output ("{0,7} : Number of days data in database" -f ($NumDays).ToString("#,###"))
Write-Output " "

$Query = "Select COUNT(ipaddress) AS countip from hm_fwban WHERE flag IS NULL"
MySQLQuery($Query) | ForEach {
	$TotalRules = $_.countip
}
Write-Output ("{0,7} : Total number of firewall rules" -f ($TotalRules).ToString("#,###"))
Write-Output " "

$Query = "Select COUNT(DISTINCT(ipaddress)) AS totalreturnips, COUNT(ipaddress) AS totalhits FROM hm_fwban_rh"
MySQLQuery($Query) | ForEach {
	$TotalReturnIPs = $_.totalreturnips
}

$PercentReturns = ([int]$TotalReturnIPs / [int]$TotalRules).ToString("P")
$NeverBlocked = ([int]$TotalRules - [int]$TotalReturnIPs)
$PercentNever = ([int]$NeverBlocked / [int]$TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs never blocked" -f ($NeverBlocked).ToString("#,###"), $PercentNever)
Write-Output " "
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 1 day" -f ($TotalReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 1"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$NumHits = $Rows.totalhits
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 2 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 2"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 3 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 3"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 4 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 4"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 5 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 5"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 6 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 6"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 7 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 7"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 8 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 8"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 9 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 9"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 10 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 10"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 11 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 11"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 12 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 12"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 13 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 13"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 14 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 14"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 15 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 15"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 16 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 16"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 17 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 17"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 18 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 18"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 19 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 19"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 20 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 20"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 21 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 21"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 22 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 22"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 23 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 23"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 24 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 24"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 25 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 25"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 26 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 26"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 27 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 27"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 28 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 28"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 29 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 29"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 30 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 30"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 31 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 31"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 32 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 32"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 33 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 33"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 34 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 34"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 35 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 35"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 36 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 36"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 37 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 37"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 38 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 38"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 39 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 39"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 40 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 40"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 41 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 41"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 42 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 42"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 43 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 43"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 44 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > 44"
$Rows = @(MySQLQuery($Query))
$ReturnIPs = $Rows.Rows.Count
$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least 45 days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)

Write-Output " "
Write-Output " "
