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

###   MYSQL VARIABLES   ####################################################
#                                                                          #
$MySQLAdminUserName = 'hmailserver'                                        #
$MySQLAdminPassword = 'supersecretpassword'                                #
$MySQLDatabase      = 'hmailserver'                                        #
$MySQLHost          = 'localhost'                                          #
#                                                                          #
############################################################################

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

$a = 0
$b = 1

Do {
	$Query = "SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > $a"
	$Rows = @(MySQLQuery($Query))
	$ReturnIPs = $Rows.Rows.Count
	$PercentReturns = ($ReturnIPs / $TotalRules).ToString("P")
	Write-Output ("{0,7} : {1,6} : Number of return IPs blocked on at least $b days" -f ($ReturnIPs).ToString("#,###"), $PercentReturns)
	$a++
	$b++
} Until ($ReturnIPs -lt 1)

Write-Output " "
Write-Output " "