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

# Include required files
Try {
	.("$PSScriptRoot\Config.ps1")
	.("$PSScriptRoot\CommonCode.ps1")
}
Catch {
	Write-Output "$((get-date).ToString(`"yy/MM/dd HH:mm:ss.ff`")) : ERROR : Unable to load supporting PowerShell Scripts : $query `n$Error[0]" | out-file "$PSScriptRoot\PSError.log" -append
}

$EmailBody = "$PSScriptRoot\BlockCountEmailBody.txt"

#	Delete old files if exist
If (Test-Path $EmailBody) {Remove-Item -Force -Path $EmailBody}

Write-Output '
<!DOCTYPE html> 
<html>
<head>
<title>hMailServer Firewall Ban</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Style-Type" content="text/css">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="wrapper">
' | Out-File $EmailBody -append

Write-Output '

hMailServer Firewall Ban Project<br>
Block Count<br>
Count repeat drops from firewall log<br><br>

' | out-file $EmailBody -append

$StartTime = get-date

Write-Output "Run : $(Get-Date -f g)<br><br>" | out-file $EmailBody -append

#	Find oldest database entry and count days.
$Query = "Select MIN(timestamp) AS mints FROM hm_fwban"
MySQLQuery($Query) | ForEach {
	$Oldest = $_.mints
}
$NumDays = (New-TimeSpan $Oldest $(Get-Date)).Days

Write-Output ("{0,7} : Number of days data in database<br><br>" -f ($NumDays).ToString("#,###")) | out-file $EmailBody -append

#	Count number of bans in firewall ban database
$Query = "Select COUNT(ipaddress) AS countip from hm_fwban WHERE flag IS NULL"
MySQLQuery($Query) | ForEach {
	$TotalRules = $_.countip
}
Write-Output ("{0,7} : Total number of IPs banned<br><br>" -f ($TotalRules).ToString("#,###")) | out-file $EmailBody -append

#	Count number of distinct IPs recorded in repeat hit database
$Query = "Select COUNT(DISTINCT(ipaddress)) AS totalreturnips, COUNT(ipaddress) AS totalhits FROM hm_fwban_rh"
MySQLQuery($Query) | ForEach {
	$TotalReturnIPs = $_.totalreturnips
}

#	Subtract distinct IPs in RH database from number of bans in FWB database to derive number of FWBans that never returned
$PercentReturns = ([int]$TotalReturnIPs / [int]$TotalRules).ToString("P")
$NeverBlocked = ([int]$TotalRules - [int]$TotalReturnIPs)
$PercentNever = ([int]$NeverBlocked / [int]$TotalRules).ToString("P")
Write-Output ("{0,7} : {1,6} : Number of banned IPs that never returned<br><br>" -f ($NeverBlocked).ToString("#,###"), $PercentNever) | out-file $EmailBody -append

#	Find number of distinct IPs that were blocked for a given number of days and continue until no results are found
$a = 0
Write-Output "
	<table cellpadding='5'>
	<tr style='text-align:center;'><td>No. of banned IPs</td><td>Percent Returns</td><td>Returned at least</td></tr>" | out-file $EmailBody -append
Do {
	$Query = "SELECT COUNT(*) AS countips FROM (SELECT ipaddress, COUNT(DISTINCT(DATE(timestamp))) AS countdate FROM hm_fwban_rh GROUP BY ipaddress HAVING countdate > $a) AS returnhits"
	MySQLQuery($Query) | ForEach {
		$ReturnIPs = $_.countips
	}
	$PercentReturns = ($ReturnIPs / $TotalRules)
	If ($ReturnIPs -lt 1) {
		Write-Output "</table><br>No more results<br><br>" | out-file $EmailBody -append
		$TimeElapsed = (New-TimeSpan $StartTime $(get-date))
		If (($TimeElapsed).Minutes -eq 1) {$sm = ""} Else {$sm = "s"}
		If (($TimeElapsed).Seconds -eq 1) {$ss = ""} Else {$ss = "s"}
		Write-Output ("Time Elapsed: {0:%m} minute$sm {0:%s} second$ss" -f $TimeElapsed) | out-file $EmailBody -append
	} Else {
		If ($a -eq 0) {$sd = ""} Else {$sd = "s"}
		Write-Output ("<tr style='text-align:center;'><td> {0,7} </td><td> {1,6} </td><td><a href='$wwwURI/blocks-view.php?submit=Search&days=$($a + 1)'>$($a + 1) day$sd</a></td></tr>" -f ($ReturnIPs).ToString("#,###"), $PercentReturns.ToString("P")) | out-file $EmailBody -append
	}
	$a++
} Until ($ReturnIPs -lt 1)

Write-Output '
</table>
<br><br>
<div class="footer"></div>
</div> <!-- end WRAPPER -->
</body>
</html>
' | Out-File $EmailBody -append

$HTML = 'True'
EmailResults $HTML