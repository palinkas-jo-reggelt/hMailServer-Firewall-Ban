<#
_  _ _  _  _  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /_\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/   \| |___ ___] |___ |  \  \/  |___ |  \     
____ _ ____ ____ _ _ _  _  _    _       ___   _  _  _ 
|___ | |__/ |___ | | | /_\ |    |       |__] /_\ |\ | 
|    | |  \ |___ |_|_|/   \|___ |___    |__]/   \| \| 

.SYNOPSIS
	Deduplicate + De-Orphan Firewall Rules

.DESCRIPTION
	Removes duplicate firewall rules and orphans (rules that should have been deleted upon release)

.FUNCTIONALITY
	* Reads firewall rules & selects only ones named as IP (will not select consolidated date rules)
	* Finds duplicates in list and deletes them
	* Finds orphans and deletes them

.NOTES
	* Create scheduled task to run daily
	* Best time to run scheduled task is immediately after running hmsConsolidateRules.ps1
	
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

#	Establish files and regex
$FWRuleList = "$PSScriptRoot\fwrulelist.txt"
$DupList = "$PSScriptRoot\fwduplist.txt"
$RegexIP = '(([0-9]{1,3}\.){3}[0-9]{1,3})'

#	Read rules from firewall and output only ones with IP name (unconsolidated rules)
Get-NetFirewallRule | foreach-object {
	if ($_.DisplayName -match $RegexIP){
	write-output $_.DisplayName
	}
} | out-file $FWRuleList

#	Find duplicates and output as list
$A = Get-Content $FWRuleList
$HT = @{}
$A | ForEach {$HT["$_"] += 1}
$HT.Keys | Where {$HT["$_"] -gt 1} | ForEach { Write-Output $_ } | Out-File $DupList

#	Delete rules from duplicate list and re-create them as a single rule
Get-Content $DupList | ForEach {
	& netsh advfirewall firewall delete rule name=`"$_`"
	& netsh advfirewall firewall add rule name="$_" description="Rule added $((get-date).ToString('MM/dd/yy')) - DUP" dir=in interface=any action=block remoteip=$_
}

#	Read IP named rule list again and look for orphans
Get-Content $FWRuleList | ForEach {
	$IP = $_
	#	Query all IPs and find flag status
	$Query = "SELECT flag FROM hm_fwban WHERE ip = '$IP'"
	MySQLQuery $Query | ForEach {
		$Flag = $_.flag
		Write-Output $Flag
	}
	#	If flag not null, then rule should not exist, so delete it
	If ($Flag -ne $NULL) {
		& netsh advfirewall firewall delete rule name=`"$IP`"
	}
}