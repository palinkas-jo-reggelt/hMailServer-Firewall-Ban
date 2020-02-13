```
_  _ _  _  __  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /__\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/    \| |___ ___] |___ |  \  \/  |___ |  \     
____ _ ____ ____ _ _ _  __  _    _       ___   __  _  _ 
|___ | |__/ |___ | | | /__\ |    |       |__] /__\ |\ | 
|    | |  \ |___ |_|_|/    \|___ |___    |__]/    \| \| 

```

Ban Spammers to Windows Defender Firewall. Use of various reject methods in EventHandlers.vbs to call Firewall Ban. Integrated web admin.


## !! NEW !! MUST READ !!

Major update. I've overhauled several files in order to eliminate Powershell's NetFirewall commands, which are incompatible with Windows versions before 8.1 Server 2012. Additionally, I've added PTR to the database. Lastly, I made it possible for VERY busy systems to use this project by splitting consolidated firewall rules into groups of 400 IPs each. Therefore, if you are attempting to ban thousands of IPs per day, the firewall won't crash on rule creation.

For NEW installations, just follow the instructions.

For UPGRADING, run hmsRetroAddPTR.ps1 and hmsRetroAddRuleName.ps1 BEFORE upgrading hmsFirewallBan.ps1. Be sure to review EventHandlers.vbs for changes to add PTR.


## !! NEW !! MUST READ !!

Recent updates have dramatically changed how firewall rules are created. When an IP gets banned, a firewall rule is created that contains only that IP as the remote address to block. This is how it has been since the beginning of this project. However, I found that too many rules (>10,000) caused issues with mysql connections and probably other things that I did not notice. Therefore, I changed the rule creation to be one rule per day that contains all of that day's banned IPs.

It works as normal for the current day. Therefore, there are no changes to EventHandlers.vbs. A handler script - hmsConsolidateRules.ps1 - should be run daily at 12:01 am. This script consolidates the previous day's firewall rules into a single rule. A scheduled task should be created for this.

For working installations, you will want to retroactively consolidate all of your rules for days previous to yesterday. For this task, use hmsConsolidateRulesRetroactively.ps1. This should only be run once and is NOT required for fresh installs.

Obviously, if you're upgrading, you will want to copy all of the files to your installation, but particularly hmsFirewallBan.ps1 and hmsConsolidateRules.ps1 are required.


## Prerequisites

1) Working hMailServer 5.7.0
2) Working MySQL with hmailserver database
3) Working Apache
4) *May* require updating Powershell
5) *May* require MySQL-Connector-Net found here: https://dev.mysql.com/downloads/connector/net/


## Instructions

1) Copy everything from EventHandlers.vbs into your EventHandlers.vbs (default location: C:\Program Files (x86)\hMailServer\Events\EventHandlers.vbs)
2) Copy vbsjson.vbs to hMailServer Events folder (default location: C:\Program Files (x86)\hMailServer\Events)
3) Install RvdH's DNS resolver (https://d-fault.nl/files/)
4) Copy RvdH's Disconnect.exe to hMailServer Events folder (https://d-fault.nl/files/)
5) Edit variables in hmsFirewallBan.ps1
6) Change group policy for firewall log to log dropped connections. Set log location to match with path in hmsFirewallBan.ps1 (or change path in hmsFirewallBan.ps1). From cmd/administrator:
```
netsh advfirewall set allprofiles logging filename "C:\scripts\hmailserver\fwban\pfirewall.log"
netsh advfirewall set allprofiles logging droppedconnections enable
```
   + You may need to edit this with Group Policy Editor. You may also need to give NT SERVICE\MPSSVC full control permissions on the folder the log resides in or the log may not automatically roll over after reaching maximum size. See here: https://serverfault.com/a/859949

7) Create scheduled task to run every 5 minutes with action: 
	+ ```powershell -executionpolicy bypass -File C:\scripts\FirewallBan\hmsFirewallBan.ps1```
!!! TASK MUST BE RUN WITH HIGHEST PRIVILEGES !!! Or powershell will fail to create/delete firewall rules on grounds of permissions. 
8) Create scheduled task to run DAILY AT 12:01 am with actions: 
	+ ```powershell -executionpolicy bypass -File C:\scripts\FirewallBan\hmsConsolidateRules.ps1```
	+ ```powershell -executionpolicy bypass -File C:\scripts\FirewallBan\hmsDuplicateRuleFinder.ps1```
!!! TASK MUST BE RUN WITH HIGHEST PRIVILEGES !!! Or powershell will fail to create/delete firewall rules on grounds of permissions. 
9) Copy the files in /www/ to your webserver and edit the db info in cred.php and edit .htaccess to allow your subnet.
10) Sit back and watch your firewall rule count grow while your spam logs get quiet.


## MySQL Create Table

Moved to hmsFirewallBan.ps1 - will be created at first run.

   
## Flag Logic

```
Flag	Meaning
====	=======
NULL	Has been added as a firewall rule
1   	Has been released from firewall (firewall rule deleted)
2   	Marked for release by manual release (release.php) but not firewall rule not yet deleted - after firewall rule deleted, reset flag to 1
3   	Marked for reban by manual reban (reban.php) but firewall rule not yet added - after firewall rule added, reset flag to NULL
4   	Default entry - signifies IP has been added to database but firewall rule has not yet been added - after firewall rule added, reset flag to NULL
5   	Marked SAFE in webadmin but firewall rule not yet deleted - permanently removes firewall rule and prevents future bans
6   	Marked SAFE and firewal rule deleted
7   	Marked for removal from SAFE list and firewall rule added
```


## Security Notes

Security is provided by Apache. You will not want the web admin to be publicly available. The .htaccess restricts access to localhost and your LAN subnet only. If you want to allow access to the WAN, I strongly suggest you password protect the directory or something else that will keep outsiders out as they will have the ability to control your firewall.


## Other Notes

I ran across an issue where a single IP hammered my server enough times to cause ip-api.com to rate limit me (150/minute). Besides that, since firewall rules get added on an interval (via scheduled task / powershell), many connections between the interval can add redundant IPs to the rule list. To get around both of these issues I setup RvdH's disconnect and SorenR's autoban. On each trigger now, three functions are called:

1) Disconnect
2) Firewall Ban
3) Autoban

This way, autoban will prevent the same IP from getting to any of my filters and thereby prevent calling firewall ban multiple times for the same IP. This will drastically reduce the number of redundant IP firewall rules and redundant IP entries in the database. If you setup your EventHandlers.vbs this way from the very beginning, you will only possibly have duplicate IPs in the database after an IP has been released. 


## Intrusion Dection System (IDS)

IDS credit to SorenR: https://www.hmailserver.com/forum/viewtopic.php?p=209545#p209545

IDS is very simple, but pure genius. It counts the number of connections that did not complete a transaction: either by accepting a message or by logon. Three strikes and you're out. When an IP has three strikes it gets added to the firewall ban with ban reason "IDS" and the IP is removed from the IDS count.


## Changelog

- 0.76 cleanup PHP from adding MSSQL changes
- 0.75 housekeeping
- 0.74 housekeeping
- 0.73 pulled MSSQL changes to php from lcamilo
- 0.72 updated stats.php
- 0.71 housekeeping to powershell scripts; fixed firewall rule deduplicator so it actually deduplicates
- 0.70 revamped PHP to support MSSQL
- 0.69 oops... forgot to add config.ps1
- 0.68 replaced INI with ps1; moved CREATE TABLEs to hmsFirewallBanDBSetup.ps1 for first run; housekeeping
- 0.67 Added MSSQL Server Support for PowershellScripts and EventHandler Code; INI file to EventHandler configurations; CommonCode.ps1 to store common code between all PowerShell scripts
- 0.66 improved powershell email function
- 0.65 consolidated user variables into a single INI file; added INI parsing and array inclusion to powershell files
- 0.64 bug fix - stoopid typo
- 0.63 housekeeping
- 0.62 changed IP Range add/release to work with actual CIDR ranges (/22 - /32)
- 0.61 added PTR to database (no more "gethostbyaddr" - PTR called locally from database); added rule splitting so maximum IPs/rule = 400; removed all reference to NetFirewall Powershell cmdlet and replaced with Netsh for compatibility;
- 0.60 added popup PTR detail on search.php (PTR derived via PHP "gethostbyaddr")
- 0.59 housekeeping
- 0.58 added hmsDuplicateRuleFinder.ps1 to find duplicate and orphaned firewall rules
- 0.57 made changes to hmsConsolidateRules.ps1 to prevent accidental firewall rule banning all local and remote IPs :)
- 0.56 created hmsConsolidateRules.ps1 and hmsConsolidateRulesRetroactively.ps1 to handle consolidation of firewall rules into daily rules 
- 0.55 bug fixes in php pages + hmsFirewallBan.ps1
- 0.54 bug fixes in hmsFirewallBan.ps1
- 0.53 bug fixes in EventHandlers.vbs; moved table creation to hmsFirewallBan.ps1 for automatic creation
- 0.52 cleaned up obsolete items in EventHandlers.vbs
- 0.51 housekeeping in EventHandlers.vbs; added blocks analyzer pages 
- 0.50 simplified BlockCount.ps1; automated end of script
- 0.49 added BlockCount.ps1 to analyze firewall drops
- 0.48 added fixed query time to powershell in order to keep the query intervals aligned
- 0.47 housekeeping changes to powershell
- 0.46 housekeeping changes to blocks pages
- 0.45 housekeeping
- 0.44 cleaned up many bugs related to IDS; housekeeping
- 0.43 added SorenR's IDS (Intrusion Detection System) and webadmin pages; now REQUIRE event OnHELO (hMailServer 5.7.0); OnHELO events updated in EventHandlers.vbs; housekeeping 
- 0.42 hmsFirewallBan.ps1: added check to see if hmailserver is running. If not, exit script. MySQL is a dependency of hmailserver. I have found that sometimes the scheduled task runs at bootup before MySQL is running, causing an error that prevents the task from running at its next scheduled interval.
- 0.41 fixed chart SQL to include NULL data  
- 0.40 streamlined chart SQL; updated stats.php; added repeats links to search.php; minor housekeeping  
- 0.39 bug fixes 
- 0.38 minor formatting changes 
- 0.37 added more pages to handle repeats; refined existing pages; added new flags to handle marking IPs SAFE (permanently released); cleaned up hmsFirewallBan.ps1; new charts for IPs blocked
- 0.36 added read firewall log to see how many "repeat customers" there are; added pages to handle firewall log entries; changed hits per day chart to add blocked IPs and now 2 trendlines
- 0.35 changed Hits Per Day chart from polynomial regression to plain after crazy out of whack result line appeared (google says to beware of skewing); moved all of chart javascript into chart php files for housekeeping purposes
- 0.34 improved reban IP elements
- 0.33 minor formatting changes; added stats.php which is the same as index.php except with a) no links and b) no includes except cred.php and is meant as a public information page
- 0.32 added NEW FLAG = "4"; EventHandlers.vbs must be changed to reflect this change as all new hits are recorded into the database with flag=4; the reason for this is to distinguish and count IPs that are new/newly released/rebanned/manually banned; if you upgrade, you must change function fwban in EventHandlers.vbs to insert flag value = 4; added np.php (not yet processed) to view new/newly released/rebanned/manually banned entries; added count new/newly released/rebanned/manually banned entries on index.php; changes to hmsFirewallBan.ps1 to process these other changes
- 0.31 added delete duplicate entries control to powershell script; tweaked index.php for duplicate entries; added LockFile function to EventHandlers.vbs
- 0.30 added duplicate entries table
- 0.29 changed "Top 5 spammer IPs" to "Last 5 duplicate IPs" which is far more informative, I think. Ultimately, you don't want duplicates at all, unless its a return after expiring a firewall rule. Its a good way to see if your hMailServer filters/triggers are working like a finely tuned machine.
- 0.28 added plural function for "hit"/"hits"; rounded avg hits per hour data to 1 decimal; changed db queries in hmsFirewallBan.ps1 from "WHERE something =" to "WHERE something LIKE"
- 0.27 removed "months" pages as redundant; search handles month views now
- 0.26 removed current day from "hits per day" chart so as not to skew the trendline with low number of hits early in the day; removed "history" pages because they're useless after accumulating thousands of hits (there's no point to it); in place of "history", search page defaults to ALL records
- 0.25 changed "Hits per hour" chart from total hits per hour to average hits per hour
- 0.24 converted chart data from strings to 'number', 'date' & 'timeofday' for better formatting; added regression to hits per day chart
- 0.23 proper credits for hmailserver functions and logic update to reduce redundant IPs
- 0.22 minor tweaks; added minDate to jquery datepicker to match oldest record in database
- 0.21 added chart to stats page
- 0.20 minor tweaks
- 0.19 bug fixes; fixed up search pages
- 0.18 bug fixes; added dropdown select for release status on search page
- 0.17 bug fixes; reban single IP rebans ALL matching IPs, not just an individual record; release single IP releases all matching IPs, not just a single record
- 0.16 bug fixes
- 0.15 Added review before release/reban
- 0.14 & before:  too many to list/forgot

