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

# Include required files
Try {
	.("$PSScriptRoot\Config.ps1")
	.("$PSScriptRoot\CommonCode.ps1")
}
Catch {
	Write-Output "Error while loading supporting PowerShell Scripts" | Out-File -Path "$PSScriptRoot\PSError.log"
}

#	Establish Duplicate Rules Folder
$DupFolder = "$PSScriptRoot\DuplicateRules"

#	Create ConsolidateRules folder if it doesn't exist
If (-not(Test-Path $DupFolder)) {
	md $DupFolder
}

#	Establish files and regex
$FWRuleList = "$DupFolder\fwrulelist.txt"
$DupList = "$DupFolder\fwduplist.txt"
$RegexIP = '(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)'
$RegexConsName = 'hMS\sFWBan\s20[0-9]{2}\-[0-9]{2}\-[0-9]{2}(_[0-9]{2})?'
$NL = [System.Environment]::NewLine

#	Read rules from firewall and output only ones with IP name (unconsolidated rules)
Get-NetshFireWallrule ("all") | ForEach {
	If (($_.RuleName -match $RegexIP) -or ($_.RuleName -match $RegexConsName)){
		Write-Output $_.RuleName
	}
} | out-file $FWRuleList

#	Find duplicates and output as list
$A = Get-Content $FWRuleList
$HT = @{}
$A | ForEach {$HT["$_"] += 1}
#	For each duplicate, get RemoteIPs and delete firewall ALL duplicate rules
$HT.Keys | Where {$HT["$_"] -gt 1} | ForEach {
	$RuleName = $_
	$RuleNameFile = "$DupFolder\$RuleName.txt"
	Get-NetshFireWallrule $RuleName | ForEach {
		Write-Output $_.RemoteIP
	} | Out-File $RuleNameFile
	& netsh advfirewall firewall delete rule name=`"$RuleName`"
}

#	Look in Duplicate Rules folder and massage the data
Get-ChildItem $DupFolder | Where-Object {($_.name -match "$RegexIP.txt") -or ($_.name -match "$RegexConsName.txt")} | ForEach {
	$RuleNameFileIP = $_.name
	$RuleData = Get-Content -Path "$DupFolder\$RuleNameFileIP" | Select-Object -First 1
	#	Make sure txt file is populated with IP data (if not, you'll have a rule banning all local and all remote IPs)
	If ($RuleData -match $RegexIP){
		#	Remove duplicate RemoteIP strings, remove /32 from RemoteIP, remove NewLines, remove comma at end of RemoteIP string, then add one firewall rule to replace duplicates
		Get-Content -Path "$DupFolder\$RuleNameFileIP" | Select -First 1 | Out-File "$DupFolder\$RuleNameFileIP.ip.txt"
		(Get-Content -Path "$DupFolder\$RuleNameFileIP.ip.txt") -Replace '\/32','' | Set-Content -Path "$DupFolder\$RuleNameFileIP.ip.txt"
		(Get-Content -Path "$DupFolder\$RuleNameFileIP.ip.txt") -Replace $NL,'' | Set-Content -Path "$DupFolder\$RuleNameFileIP.ip.txt"
		(Get-Content -Path "$DupFolder\$RuleNameFileIP.ip.txt") -Replace ',$','' | Set-Content -Path "$DupFolder\$RuleNameFileIP.ip.txt"
		$FWRN = $RuleNameFileIP.Split(".")[0]
		& netsh advfirewall firewall add rule name="$FWRN" description="Rule added $((get-date).ToString('MM/dd/yy')) - DUP" dir=in interface=any action=block remoteip=$(Get-Content "$FWRN.txt.ip.txt")
	}
}

#	Delete all files in the Duplicate Rules Folder because they cause trouble on the next run.
Get-ChildItem -Path $DupFolder -Include * | foreach { $_.Delete()}