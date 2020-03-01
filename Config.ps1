<#
_  _ _  _  _  _ _    ____ ____ ____ _  _ ____ ____     
|__| |\/| /_\ | |    [__  |___ |__/ |  | |___ |__/     
|  | |  |/   \| |___ ___] |___ |  \  \/  |___ |  \     
____ _ ____ ____ _ _ _  _  _    _       ___   _  _  _ 
|___ | |__/ |___ | | | /_\ |    |       |__] /_\ |\ | 
|    | |  \ |___ |_|_|/   \|___ |___    |__]/   \| \| 

.SYNOPSIS
	Config File

.DESCRIPTION
	Config File

.FUNCTIONALITY

.NOTES

.EXAMPLE

#>

###   MYSQL VARIABLES   ################################################################
#                                                                                      #
$DatabaseType     = 'MYSQL'            #<-- Options: "MYSQL" or "MSSQL"                #
$SQLAdminUserName = 'hmailserver'                                                      #
$SQLAdminPassword = 'supersecretpassword'                                              #
$SQLDatabase      = 'hmailserver'                                                      #
$SQLHost          = '127.0.0.1'                                                        #
$SQLPort          = 3306                                                               #
#                                                                                      #
###   FIREWALL VARIABLES   #############################################################
#                                                                                      #
$LANSubnet        = '192.168.1'  # <-- 3 octets only, please                           #
$MailPorts        = '25|465|587|110|995|143|993' # <-- add custom ports if in use      #
$FirewallLog      = 'C:\scripts\hmailserver\FWBan\Firewall\pfirewall.log'              #
#                                                                                      #
###   INTERVAL VARIABLES   #############################################################
#                                                                                      #
$Interval         = 5   # <-- (minutes) must match the frequency of Win Sched Task     #
$IDSExpire        = 12  # <-- (hours) expire IDS entries that have not resulted in ban #
#                                                                                      #
###   PHP VARIABLES   ##################################################################
#                                                                                      #
$wwwFolder        = "C:\xampp\htdocs\mydomain\fwban" # <-- www folder location         #
$wwwURI           = "https://firewallban.dynu.net"   # <-- no trailing slash, please   #
#                                                                                      #
###   EMAIL VARIABLES   ################################################################
#                                                                                      #
$FromAddress      = 'notifier.account@gmail.com'                                       #
$Recipient        = 'me@mydomain.com'                                                  #
$SMTPServer       = 'smtp.gmail.com'                                                   #
$SMTPAuthUser     = 'notifier.account@gmail.com'                                       #
$SMTPAuthPass     = 'supersecretpassword'                                              #
$SMTPPort         = 587                                                                #
$SSL              = 'True'                                                             #
#                                                                                      #
########################################################################################