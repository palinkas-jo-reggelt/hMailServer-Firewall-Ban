# hMailServer Firewall Ban

## Prerequisites

1) Working hMailServer
2) Working MySQL with hmailserver database


## Optional Components

1) RvdH's DNS resolver if you want to lookup & reject based on zen.spamhaus.org. (https://d-fault.nl/files/
2) VbsJson.vbs in order to lookup GeoIP listings. https://github.com/eklam/VbsJson/blob/master/VbsJson.vbs

## DB SQL Create Table

```
CREATE TABLE hm_fwban (
	id int NOT NULL AUTO_INCREMENT UNIQUE,
	ipaddress VARCHAR (192) NOT NULL,
	timestamp TIMESTAMP,
	ban_reason VARCHAR (192),
	countrycode VARCHAR (4),
	country VARCHAR (192),
	flag INT (1) NULL DEFAULT,
	PRIMARY KEY (id)
); 
```
   
## Instructions

1) Copy everything from EventHandlers.vbs into your EventHandlers.vbs (C:\Program Files (x86)\hMailServer\Events\EventHandlers.vbs)
2) Copy vbsjson.vbs to hMailServer Events folder (C:\Program Files (x86)\hMailServer\Events)
3) Install RvdH's DNS resolver (https://d-fault.nl/files/)
4) Edit db variables in hmsFirewallBan.ps1
5) Using phpMyAdmin or whatever you want, add table "hm_fwban" to hmailserver database.
6) Create scheduled task to run every 5 minutes with action: 
```powershell -executionpolicy bypass -File C:\scripts\checkstate\hmsFirewallBan.ps1```
!!! TASK MUST BE RUN WITH HIGHEST PRIVILEGES !!! Or powershell will fail to create/delete firewall rules on grounds of permissions. 
7) Copy the files in /www/ to your webserver and edit the db info in cred.php.
8) Sit back and watch your firewall rule count grow.


## Flag Logic

```
Flag	Meaning
====	=======
NULL	Default - has been added to firewall rule
1   	Marked as released by auto expire after removing firewall rule
2   	Marked for release by manual release (release.php), after firewall rule deleted, reset flag to 1
3   	Marked for reban by manual reban (reban.php), after firewall rule added, reset flag to NULL
```