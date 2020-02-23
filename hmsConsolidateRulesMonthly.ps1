<#
_  _ _  _  _  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /_\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/   \| |___ ___] |___ |  \  \/  |___ |  \     
____ _ ____ ____ _ _ _  _  _    _       ___   _  _  _ 
|___ | |__/ |___ | | | /_\ |    |       |__] /_\ |\ | 
|    | |  \ |___ |_|_|/   \|___ |___    |__]/   \| \| 

.SYNOPSIS
	Powershell script to consolidate daily firewall rules into monthly firewall rules

.DESCRIPTION
	Powershell script to consolidate daily firewall rules into monthly firewall rules

.FUNCTIONALITY
	* Queries firewall for previous month's rules
	* Creates new firewall rules containing all of previous month's banned IPs 
	* Deletes all of previous month's daily firewall rules

.NOTES
	* Create scheduled task to run once per day at 12:01 am (will run only on the first of the month)
	
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

#	Run nightly but only trigger on the first of the month
If ((Get-Date).ToString("dd") -notmatch '01') { Exit }

$ConsFolder = "$PSScriptRoot\ConsolidateRulesMonthly"
$EmailBody = "$PSScriptRoot\ConsolidateRulesMonthly\EmailBody.txt"

#	Create ConsolidateRules folder if it doesn't exist
If (-not(Test-Path $ConsFolder)) {
	md $ConsFolder
}

#	Delete all files in the Consolidated Rules folder before beginning
Get-ChildItem -Path $ConsFolder -Include * | foreach { $_.Delete()}

#	Get BanDate (last month) and establish csv
$BanDate = (Get-Date).AddMonths(-1).ToString("yyyy-MM")

#	Set up email result
Write-Output "hMailServer Firewall Ban `n`nMonthly Rule Consolidation `n`n" | Out-File $EmailBody -Append

#	Establish files and regex
$FWRuleList = "$ConsFolder\fwrulelist.txt"
$RemoteIPList = "$ConsFolder\remoteiplist.txt"
$RegexConsName = 'hMS\sFWBan\s20[0-9]{2}\-[0-9]{2}\-[0-9]{2}(_[0-9]{2})?'
$NL = [System.Environment]::NewLine

#	Read rules from firewall and output only ones from last month
Get-NetshFireWallrule ("all") | ForEach {
	If (($_.RuleName -match $RegexConsName) -and ($_.RuleName -match $BanDate)){
		Write-Output $_.RuleName
	}
} | Out-File $FWRuleList

#	Get list of rulenames, read RemoteIP from each rule, then output to a single list
Get-Content -Path $FWRuleList | ForEach {
	$RuleDay = $_
	Get-NetshFireWallrule ($RuleDay) | ForEach {
		$RemoteIP = $_.RemoteIP
		$ReplaceCIDR = ($RemoteIP).Replace("/32", "")
		Write-Output $ReplaceCIDR 
	}
} | Out-File $RemoteIPList

#	convert list to single string
(Get-Content -Path $RemoteIPList) -Replace '$',',' | Set-Content -NoNewline -Path $RemoteIPList
(Get-Content -Path $RemoteIPList) -Replace ',$','' | Set-Content -NoNewline -Path $RemoteIPList

#	Create new list for the purpose of updating rulename in database
(Get-Content -Path $RemoteIPList) -Replace ',',$NL | Out-File "$RemoteIPList.updater.txt"
$CountRemoteIPs = (Get-Content -Path "$RemoteIPList.updater.txt").Count
Write-Output "There are $CountRemoteIPs IPs contained in $((Get-Content $FWRuleList).Count) firewall rules in the month of $((Get-Date $BanDate).ToString("MMMM")) $((Get-Date $BanDate).ToString("yyyy"))`n`n" | Out-File $EmailBody -Append

$N = 0
$Rows = 400
$Limit = [math]::ceiling($CountRemoteIPs / $Rows)

If ($Limit -eq 0){
	Write-Output "WARNING - there are no rules to consolidate `n`nExiting operation - Bye..." | Out-File $EmailBody -Append
	EmailResults
	Exit
}
ElseIf ($Limit -eq 1){
	#	Add rule for last month
	$MonthRuleName = "hMS FWBan $BanDate"
	& netsh advfirewall firewall add rule name="$MonthRuleName" description="FWB Rules for $BanDate" dir=in interface=any action=block remoteip=$(Get-Content $RemoteIPList)
	Write-Output "Creating firewall rule : $MonthRuleName" | Out-File $EmailBody -Append

	#	Delete last month's rules
	Get-Content -Path $FWRuleList | ForEach {
		$RuleToDelete = $_
		& netsh advfirewall firewall delete rule name=`"$RuleToDelete`"
		Write-Output "Deleting firewall rule : $RuleToDelete" | Out-File $EmailBody -Append
	}

	#	Update rulenames in database
	Get-Content -Path "$RemoteIPList.updater.txt" | ForEach {
		$IP = $_
		$Query = "UPDATE hm_fwban SET rulename='$MonthRuleName' WHERE ipaddress='$IP'"
		RunSQLQuery $Query
	}
}
Else {
	Do {
		#	Split up IPs into chunks for multiple rule creation
		$X = ($N).ToString("00")
		Get-Content "$RemoteIPList.updater.txt" | select -first $Rows -skip $($N * $Rows) | Out-File "$RemoteIPList.IPBatchForRemoteIP_$X.txt"
		(Get-Content "$RemoteIPList.IPBatchForRemoteIP_$X.txt") -Replace '$',',' | Set-Content -NoNewline -Path "$RemoteIPList.IPBatchForRuleCreation_$X.txt"
		(Get-Content "$RemoteIPList.IPBatchForRuleCreation_$X.txt") -Replace ',$','' | Set-Content -Path "$RemoteIPList.IPBatchForRuleCreation_$X.txt"
		
		#	Create chunk rule
		$MonthRuleName = "hMS FWBan "+$BanDate+"_"+$X
		& netsh advfirewall firewall add rule name="$MonthRuleName" description="FWB Rules for $BanDate" dir=in interface=any action=block remoteip=$(Get-Content "$RemoteIPList.IPBatchForRuleCreation_$X.txt")
		Write-Output "Creating firewall rule : $MonthRuleName" | Out-File $EmailBody -Append
		
		#	Update rulenames in database
		Get-Content "$RemoteIPList.IPBatchForRemoteIP_$X.txt" | ForEach {
			$IP = $_
			$Query = "UPDATE hm_fwban SET rulename='$MonthRuleName' WHERE ipaddress='$IP'"
			RunSQLQuery $Query
		}

		$N++
	}
	Until ($N -eq $Limit)

	#	Delete last month's rules
	Get-Content -Path $FWRuleList | ForEach {
		$RuleToDelete = $_
		& netsh advfirewall firewall delete rule name=`"$RuleToDelete`"
		Write-Output "Deleting firewall rule : $RuleToDelete" | Out-File $EmailBody -Append
	}
}

EmailResults