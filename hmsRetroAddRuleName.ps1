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

Function EmailResults {
	$Subject = "Retroactive Rule Name Results" 
	$Body = (Get-Content -Path $Msg | Out-String )
	$SMTPClient = New-Object Net.Mail.SmtpClient($ini['Email']['SMTPServer'], $ini['Email']['SMTPPort']) 
	$SMTPClient.EnableSsl = [System.Convert]::ToBoolean($ini['Email']['SSL'])
	$SMTPClient.Credentials = New-Object System.Net.NetworkCredential($ini['Email']['SMTPAuthUser'], $ini['Email']['SMTPAuthPass']); 
	$SMTPClient.Send($ini['Email']['FromAddress'], $ini['Email']['Recipient'], $Subject, $Body)
}

$StartTime = (Get-Date -f G)

#	Create folder if it doesn't exist
If (-not(Test-Path "$PSScriptRoot\RetroAddRuleName")) {
	md "$PSScriptRoot\RetroAddRuleName"
}

$NewLine = [System.Environment]::NewLine
$RegexIP = '^(([0-9]{1,3}\.){3}[0-9]{1,3})$'
$RegexDateName = 'hms\sFWBan\s20[0-9][0-9]\-[0-9][0-9]\-[0-9][0-9]$'
$RegexIPName = '^(([0-9]{1,3}\.){3}[0-9]{1,3})$'
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

# 	Add "rulename" column to hm_fwban
$Query = "ALTER TABLE hm_fwban ADD rulename VARCHAR(192) NULL $(IF (IsMySQL) {"AFTER helo"});" #MSSQL does not have this option, To achieve this, must create a new table with desired column order, copy data, drop old table, rename new table with right name.
RunSQLQuery($Query)

#	Pick up any missed entries (bans without firewall rules)
$Query = "SELECT ipaddress, id FROM hm_fwban WHERE flag IS NULL AND rulename IS NULL"
RunSQLQuery $Query | foreach {
	$ID = $_.id
	$IP = $_.ipaddress
	& netsh advfirewall firewall add rule name="$IP" description="Rule added $((get-date).ToString('MM/dd/yy'))" dir=in interface=any action=block remoteip=$IP
	$Query = "UPDATE hm_fwban SET rulename='$IP' WHERE id='$ID'"
	RunSQLQuery $Query
}

$EndTime = (Get-Date -f G)
$OperationTime = New-Timespan $StartTime $EndTime
If (($Duration).Hours -eq 1) {$sh = ""} Else {$sh = "s"}
If (($Duration).Minutes -eq 1) {$sm = ""} Else {$sm = "s"}
If (($Duration).Seconds -eq 1) {$ss = ""} Else {$ss = "s"}

$Msg = ("Retroactive RuleName update complete.`n`nUpdate completed in {0:%h} hour$sh {0:%m} minute$sm {0:%s} second$ss" -f $OperationTime)
EmailResults