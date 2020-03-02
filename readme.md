```
_  _ _  _  __  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /__\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/    \| |___ ___] |___ |  \  \/  |___ |  \     
____ _ ____ ____ _ _ _  __  _    _       ___   __  _  _ 
|___ | |__/ |___ | | | /__\ |    |       |__] /__\ |\ | 
|    | |  \ |___ |_|_|/    \|___ |___    |__]/    \| \| 

```

Ban Spammers to Windows Defender Firewall. Use of various reject methods in EventHandlers.vbs to call Firewall Ban. Integrated web admin.


## Prerequisites

1) Working hMailServer 5.7.0
2) Working MySQL OR MSSQL with hmailserver database
3) Working Apache/IIS with PHP
4) *May* require updating Powershell
5) *May* require MySQL-Connector-Net found here: https://dev.mysql.com/downloads/connector/net/


## Instructions - INSTALLING

1) Copy everything from EventHandlers.vbs into your EventHandlers.vbs (default location: C:\Program Files (x86)\hMailServer\Events\EventHandlers.vbs)
2) Copy vbsjson.vbs to hMailServer Events folder (default location: C:\Program Files (x86)\hMailServer\Events)
3) Install RvdH's DNS resolver (https://d-fault.nl/files/)
4) Copy RvdH's Disconnect.exe to hMailServer Events folder (https://d-fault.nl/files/)
5) Edit variables in Config.ps1
6) Run hmsFirewallBanDBSetup.ps1 to setup database tables.
6) Change group policy for firewall log to log dropped connections. Set log location Config.ps1. From cmd/administrator:
```
netsh advfirewall set allprofiles logging filename "C:\scripts\hmailserver\fwban\pfirewall.log"
netsh advfirewall set allprofiles logging droppedconnections enable
```
   + You may need to edit this with Group Policy Editor. You may also need to give NT SERVICE\MPSSVC full control permissions on the folder the log resides in or the log may not automatically roll over after reaching maximum size. See here: https://serverfault.com/a/859949

7) Create scheduled task to run every `5 minutes` with action: 
	+ ```powershell -executionpolicy bypass -File C:\scripts\FirewallBan\hmsFirewallBan.ps1```
!!! TASK MUST BE RUN WITH HIGHEST PRIVILEGES !!! Or powershell will fail to create/delete firewall rules on grounds of permissions. 
8) Create scheduled task to run DAILY AT 12:01 am with actions (in this order): 
	+ ```powershell -executionpolicy bypass -File C:\scripts\FirewallBan\hmsConsolidateRules.ps1```
	+ ```powershell -executionpolicy bypass -File C:\scripts\FirewallBan\hmsDuplicateRuleFinder.ps1```
	+ ```powershell -executionpolicy bypass -File C:\scripts\FirewallBan\hmsUpdateChartTables.ps1```
	+ ```powershell -executionpolicy bypass -File C:\scripts\FirewallBan\hmsConsolidateRulesMonthly.ps1```
!!! TASK MUST BE RUN WITH HIGHEST PRIVILEGES !!! Or powershell will fail to create/delete firewall rules on grounds of permissions. 
9) Copy the files in /www/ to your webserver then edit the db info in config.php and edit .htaccess to allow your subnet.
10) Sit back and watch your firewall rule count grow while your spam logs get quiet.


## Instructions - UPGRADING

If you installed or last upgraded before v.0.56 (11/27/19), you will need to run `hmsRetroConsolidateRules.ps1` and `hmsRetroAddRuleName.ps1` before proceeding with any other upgrade.

If you installed or last upgraded before v.0.61 (1/20/20), you will need to run `hmsRetroAddPTR.ps1` before proceeding with any other upgrade.

If you installed or last upgraded before v.0.77 (2/16/20), you will need to run `hmsRetroAddBlocksIPTable.ps1` before proceeding with any other upgrade.

After the above is satisfied, replace your old files with the new ones. Update EventHandlers.vbs accordingly.


## SQL Create Tables

Moved to hmsFirewallBanDBSetup.ps1

   
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

The only security is provided by .htaccess blocking connections outside your LAN. You will not want the web admin to be publicly available for obvious reasons - someone might ban you! The .htaccess restricts access to localhost and your LAN subnet only. If you want to allow access to the WAN, I strongly suggest you password protect the directory or do something else that will keep outsiders out as they will have the ability to control your firewall.


## Other Notes

I ran across an issue where a single IP hammered my server enough times to cause ip-api.com to rate limit me (150/minute). Besides that, since firewall rules get added on an interval (via scheduled task / powershell), many connections between the interval can add redundant IPs to the rule list. To get around both of these issues I setup RvdH's disconnect and SorenR's autoban. On each trigger now, three functions should be called:

1) Disconnect
2) Autoban
3) Firewall Ban

This way, autoban will temporarily block the IP and thereby prevent calling firewall ban multiple times for the same IP. This will drastically reduce the number of redundant IP entries in the database. Additionally, hmsFirewallBan deletes redundant IPs WITHIN THE 5 MINUTE INTERVAL ONLY. This method allows for duplicate IPs in the database as ban > release > ban would be 3 separate incidents and a bona fide reason to have duplicate entries. 


## Intrusion Dection System (IDS)

IDS credit to SorenR: https://www.hmailserver.com/forum/viewtopic.php?p=209545#p209545

IDS is very simple, but pure genius. It counts the number of connections that did not complete a transaction: either by accepting a message or by logon. Three strikes and you're out. When an IP has three strikes it gets added to the firewall ban with ban reason "IDS" and the IP is removed from the IDS count.


## Changelog

- 0.95 formatting on on BlockCount.ps1, hmsUpdateChartTables.ps1, blocks-ps.php
- 0.94 housekeeping on BlockCount.ps1, index.php, blocks-ps.php
- 0.93 added blocks-ps.php to launch BlockCount.ps1 which emails results (for use when blocks.php runs into execution time errors); changed EmailResults function to send HTML messages; added HTML with links back to webadmin in BlockCount.ps1; changes to config files for new variables
- 0.92 fixed typos on some queries calling my demo database (wrong table names)
- 0.91 updated hmsRetroAddBlocksIPTable.ps1 to add indexes; updated hmsUpdateChartTables.ps1
- 0.90 made queries on repeats pages more efficient, updated hmsFirewallBanDBSetup.ps1 to add indexes on mysql
- 0.89 added dials on index.php for today's info; dial yellow is 75% - 100% of maximum number of hits in a single day; dial red is 100% - 120%; started making blocks queries more efficient
- 0.88 added geoip function with 2 options: ip-api.com or GeoLite2MySQL (https://github.com/palinkas-jo-reggelt/GeoLite2MySQL); updated config.php for GeoLite2MySQL database variables
- 0.87 added hmsConsolidateRulesMonthly.ps1 which consolidates daily rules from the previous month into a series of rules with max 400 RemoteIPs
- 0.86 minor tweaks for map mobile view; limit 1 on last ban/drop
- 0.85 added country to reban-ip.php; added get country from json function 
- 0.84 added map to php
- 0.83 reverted changes to functions.php due to accidentally overwriting
- 0.82 bug fixes
- 0.81 updated installation/upgrade instructions 
- 0.80 housekeeping 
- 0.79 mssql changes by lcamilo
- 0.78 added count of blocks to index.php
- 0.77 added new table, added data caching for charts and other common references to hm_fwban_rh
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