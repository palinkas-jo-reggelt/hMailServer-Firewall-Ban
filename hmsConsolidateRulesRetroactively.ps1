<#
_  _ _  _  _  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /_\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/   \| |___ ___] |___ |  \  \/  |___ |  \     
____ _ ____ ____ _ _ _  _  _    _       ___   _  _  _ 
|___ | |__/ |___ | | | /_\ |    |       |__] /_\ |\ | 
|    | |  \ |___ |_|_|/   \|___ |___    |__]/   \| \| 

.SYNOPSIS
	Powershell script to retroactively consolidate firewall rules

.DESCRIPTION
	Consolidates rules from one firewall rule per IP to one firewall rule per day containing all IPs for the queried day

.FUNCTIONALITY
	* Queries database for each day's bans (active firewall rules)
	* Creates new firewall rule for each day containing all banned IPs for that day 
	* Deletes all one-IP-per firewall rules

.NOTES
	* FOR RETROACTIVE USE ONLY - RUN ONLY ONCE
	* TO BE USED ONLY ON ACTIVE FIREWALL BAN INSTALLATIONS 
	* Do not run on fresh installations
	* Automatically selects first day through yesterday
	
.EXAMPLE

#>

# Include required files
Try {
	.("$PSScriptRoot\Config.ps1")
	.("$PSScriptRoot\CommonCode.ps1")
}
Catch {
	Write-Output "$((get-date).ToString(`"yy/MM/dd HH:mm:ss.ff`")) : ERROR : Unable to load supporting PowerShell Scripts : $query `n$Error[0]" | out-file "$PSScriptRoot\PSError.log" -append
}

$ConsFolder = "$PSScriptRoot\ConsolidateRules"

#	Create ConsolidateRules folder if it doesn't exist
If (-not(Test-Path $ConsFolder)) {
	md $ConsFolder
}

#	Delete all files in the Consolidated Rules folder before beginning
Get-ChildItem -Path $ConsFolder -Include * | foreach { $_.Delete()}

#	Find earliest ban date 
$Query = "SELECT MIN(timestamp) AS mindate FROM hm_fwban WHERE flag IS NULL"
RunSQLQuery $Query | ForEach {
	$MinDate = (Get-Date -date $_.mindate)
}

$A = 0

Do {
	$BanDate = $MinDate.AddDays($A).ToString("yyyy-MM-dd")
	$ConsRules = "$ConsFolder\hMS FWBan $BanDate.csv"

	$Query = "
		SELECT 
			id, 
			ipaddress 
		FROM hm_fwban 
		WHERE $(DBCastDateTimeFieldAsDate('timestamp')) LIKE '$BanDate%' AND flag IS NULL
		"
	RunSQLQuery $Query | Export-CSV $ConsRules

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

	$A++

} Until ($BanDate -match $((Get-Date).AddDays(-1).ToString("yyyy-MM-dd")))
