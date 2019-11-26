<#
_  _ _  _  _  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /_\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/   \| |___ ___] |___ |  \  \/  |___ |  \     
____ _ ____ ____ _ _ _  _  _    _       ___   _  _  _ 
|___ | |__/ |___ | | | /_\ |    |       |__] /_\ |\ | 
|    | |  \ |___ |_|_|/   \|___ |___    |__]/   \| \| 

.SYNOPSIS
	Powershell script to consolidate firewall rules

.DESCRIPTION
	Consolidates rules from one firewall rule per IP to one firewall rule per day containing all IPs for the previous day

.FUNCTIONALITY
	* Queries database for previous day's bans
	* Creates new firewall rule containing all of previous day's banned IPs 
	* Deletes all of previous day's one-IP-per firewall rules

.NOTES
	* Create scheduled task to run once per day at 12:01 am
	
.EXAMPLE

#>

###   MYSQL VARIABLES   ########################################################
#                                                                              #
$MySQLAdminUserName = 'hmailserver'                                            #
$MySQLAdminPassword = 'supersecretpassword'                                    #
$MySQLDatabase = 'hmailserver'                                                 #
$MySQLHost = '127.0.0.1'                                                       #
$DBErrorLog = "$PSScriptRoot\DBErrorConsolidateRules.log"                      #
#                                                                              #
################################################################################

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
	  Write-Output "$((get-date).ToString(`"yy/MM/dd HH:mm:ss.ff`")) : ERROR : Unable to run query : $query `n$Error[0]" | Out-File $DBErrorLog -append
	 }
	Finally {
	  $Connection.Close()
	  }
}

$BanDate = (Get-Date).AddDays(-1).ToString("yyyy-MM-dd")
$ConsRules = "$PSScriptRoot\hmsFWBRule-$BanDate.csv"

$Query = "SELECT id, ipaddress FROM hm_fwban WHERE DATE(timestamp) LIKE '$BanDate%' AND flag IS NULL"
MySQLQuery $Query | Export-CSV $ConsRules

Import-CSV $ConsRules | ForEach {
	Write-Output $_.ipaddress
} | Out-File "$ConsRules.txt"

$NL = [System.Environment]::NewLine
$Content=[String] $Template= [System.IO.File]::ReadAllText("$ConsRules.txt")
$Content.Replace($NL,",") | Out-File "$ConsRules.rule.txt"
(Get-Content -Path "$ConsRules.rule.txt") -Replace ',$','' | Set-Content -Path "$ConsRules.rule.txt"

& netsh advfirewall firewall add rule name="hMS FWBan $BanDate" description="FWB Rules for $BanDate" dir=in interface=any action=block remoteip=$(Get-Content "$ConsRules.rule.txt")

Import-CSV $ConsRules | ForEach {
	$IP = $_.ipaddress
	& netsh advfirewall firewall delete rule name=`"$IP`"
}