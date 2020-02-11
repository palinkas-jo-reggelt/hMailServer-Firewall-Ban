<#
_  _ _  _  _  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /_\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/   \| |___ ___] |___ |  \  \/  |___ |  \     
____ _ ____ ____ _ _ _  _  _    _       ___   _  _  _ 
|___ | |__/ |___ | | | /_\ |    |       |__] /_\ |\ | 
|    | |  \ |___ |_|_|/   \|___ |___    |__]/   \| \| 

.SYNOPSIS
	Powershell script to retroactively add firewall rule names

.DESCRIPTION
	Adds column "rulename" to database, queries firewall for rules, extracts IP scope from rules and inserts rule name into database for each IP. 
	
.FUNCTIONALITY
	1) Fill in user variables
	2) Run script

.NOTES
	Includes email notification when complete. Also includes a search for bans with no rules to bring the firewall up to match the database.

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

#	Set start time
$StartTime = (Get-Date -f G)

#	Create folder if it doesn't exist
If (-not(Test-Path "$PSScriptRoot\RetroAddRuleName")) {
	md "$PSScriptRoot\RetroAddRuleName"
}

# 	Add "rulename" column to hm_fwban
$Query = "ALTER TABLE hm_fwban ADD rulename VARCHAR(192) NULL;"
RunSQLQuery($Query)

#	Count IPs that should get rulenames
$Query = "
	SELECT 
		COUNT(rulename) AS countnull 
	FROM hm_fwban 
	WHERE flag IS NULL
"
RunSQLQuery($Query) | ForEach {
	[int]$CountStart = $_.countnull
}

$NewLine = [System.Environment]::NewLine
$RegexIP = '(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)'
$RegexDateName = '(hmsFWBRule\-|hMS\sFWBan\s)(20\d{2}\-\d{2}\-\d{2})(_\d{3})?$'
$RegexIPName  = '^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$'
$RegexFileName = 'hms\sFWBan\s20[0-9][0-9]\-[0-9][0-9]\-[0-9][0-9].txt$'
$Location = "$PSScriptRoot\RetroAddRuleName"

Get-NetshFireWallrule ("all") | ForEach {
	If (($_.RuleName -match $RegexDateName) -or ($_.RuleName -match $RegexIPName)){
		$RuleName = $_.RuleName
		Get-NetshFireWallrule ("$RuleName") | ForEach {
			$RemoteIP = $_.RemoteIP
			$ReplaceCIDR = ($RemoteIP).Replace("/32", "")
			$ReplaceNL = ($ReplaceCIDR).Replace(",", $NewLine)
			Write-Output $ReplaceNL
		} | Out-File "$Location\$RuleName.txt"
	}
}

Get-ChildItem $Location | Where-Object {$_.name -match $RegexFileName} | ForEach {
	$RuleFileName = $_.name
	$SQLRuleName = ($RuleFileName).Replace(".txt", "")
	Get-Content -Path "$Location\$RuleFileName" | ForEach {
		If ($_ -match $RegexIP){
			$IP = $_
			$Query = "UPDATE hm_fwban SET rulename = '$SQLRuleName' WHERE ipaddress = '$IP'"
			RunSQLQuery($Query)
		}
	}
}

#	Pick up any missed entries (bans without firewall rules)
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE flag IS NULL AND rulename IS NULL"
RunSQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	& netsh advfirewall firewall add rule name="$IP" description="Rule added $((get-date).ToString('MM/dd/yy'))" dir=in interface=any action=block remoteip=$IP
	$Query = "UPDATE hm_fwban SET rulename='$IP' WHERE id='$ID'"
	RunSQLQuery $Query
}

#	Count Results
$Query = "
	SELECT 
		COUNT(rulename) AS countrulename 
	FROM hm_fwban 
	WHERE rulename IS NOT NULL AND flag IS NULL
"
RunSQLQuery($Query) | ForEach {
	[int]$CountEnd = $_.countrulename
}

$ResultCount = $CountStart - $CountEnd
If ($ResultCount -eq 0){
	$ResultMsg = "Successfully added $($CountEnd)ToString.('#,###') rulenames"
} Else {
	$ResultMsg = "UPDATE FAILED to add $(($CountStart - $CountEnd)ToString.('#,###')) rulenames - check DB Error Logs for more info"
}

$EndTime = (Get-Date -f G)
$OperationTime = New-Timespan $StartTime $EndTime
If (($Duration).Hours -eq 1) {$sh = ""} Else {$sh = "s"}
If (($Duration).Minutes -eq 1) {$sm = ""} Else {$sm = "s"}
If (($Duration).Seconds -eq 1) {$ss = ""} Else {$ss = "s"}

$EmailBody = ("Retroactive RuleName update complete `n`nResults: $ResultMsg`n`nUpdate completed in {0:%h} hour$sh {0:%m} minute$sm {0:%s} second$ss" -f $OperationTime)
EmailResults