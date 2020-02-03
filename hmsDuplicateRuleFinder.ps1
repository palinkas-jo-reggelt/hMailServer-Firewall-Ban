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

<# https://stackoverflow.com/a/422529 #>
Function Parse-IniFile ($file) {
	$ini = @{}

	$section = "NO_SECTION"
	$ini[$section] = @{}

	switch -regex -file $file {
		"^\[(.+)\]$" {
			$section = $matches[1].Trim()
			$ini[$section] = @{}
		}
		"^\s*([^#].+?)\s*=\s*(.*)" {
			$name,$value = $matches[1..2]
			if (!($name.StartsWith(";"))) {
				$ini[$section][$name] = $value.Trim()
			}
		}
	}
	$ini
}

Function MySQLQuery($Query) {
	$Today = (Get-Date).ToString("yyyyMMdd")
	$DBErrorLog = "$PSScriptRoot\$Today-DBError.log"
	$ConnectionString = "server=" + $ini['Database']['Host'] + ";port=3306;uid=" + $ini['Database']['Username'] + ";pwd=" + $ini['Database']['Password'] + ";database=" + $ini['Database']['DBase']
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

<#  https://gist.github.com/Stephanevg/a951872bd13d91c0eefad7ad52994f47  #>
Function Get-NetshFireWallrule {
	Param(
		[String]$RuleName
	)
	$Rules = & netsh advfirewall firewall show rule name="$ruleName"
	$return = @()
		$HAsh = [Ordered]@{}
		foreach ($Rule in $Rules){
			switch -Regex ($Rule){
				'^Rule Name:\s+(?<RuleName>.*$)'{$Hash.RuleName = $MAtches.RuleName}
				'^RemoteIP:\s+(?<RemoteIP>.*$)'{$Hash.RemoteIP = $Matches.RemoteIP;$obj = New-Object psobject -Property $Hash;$return += $obj}
			}
		}
	return $return
}

#	Load User Variables
$ini = Parse-IniFile("$PSScriptRoot\Config.INI")

#	Establish files and regex
$FWRuleList = "$PSScriptRoot\fwrulelist.txt"
$DupList = "$PSScriptRoot\fwduplist.txt"
$RegexIP = '^(([0-9]{1,3}\.){3}[0-9]{1,3})$'

#	Read rules from firewall and output only ones with IP name (unconsolidated rules)
Get-NetshFireWallrule ("all") | ForEach {
	If ($_.RuleName -match $RegexIP){
		Write-Output $_.RuleName
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
	$Query = "SELECT flag FROM hm_fwban WHERE ipaddress = '$IP'"
	MySQLQuery $Query | ForEach {
		$Flag = $_.flag
	}
	#	If flag not null, then rule should not exist, so delete it
	If ($Flag -ne $NULL) {
		& netsh advfirewall firewall delete rule name=`"$IP`"
	}
}