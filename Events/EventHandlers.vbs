Option Explicit

'******************************************************************************************************************************
'********** Settings                                                                                                 **********
'******************************************************************************************************************************

' 	COM authentication

Private Const ADMIN = "Administrator"
Private Const PASSWORD = "supersecretpassword"

'******************************************************************************************************************************
'********** Functions                                                                                                **********
'******************************************************************************************************************************

'	Function Include - https://www.hmailserver.com/forum/viewtopic.php?p=212052
Function Include(sInstFile)
   Dim f, s, oFSO
   Set oFSO = CreateObject("Scripting.FileSystemObject")
   On Error Resume Next
   If oFSO.FileExists(sInstFile) Then
      Set f = oFSO.OpenTextFile(sInstFile)
      s = f.ReadAll
      f.Close
      ExecuteGlobal s
   End If
   On Error Goto 0
End Function

'	Function Wait - https://www.hmailserver.com/forum/viewtopic.php?p=212052
Function Wait(sec)
   With CreateObject("WScript.Shell")
'     .Run "timeout /T " & Int(sec), 0, True                                     ' Windows 7/2003/2008 or later
'     .Run "sleep -m " & Int(sec * 1000), 0, True                                ' Windows 2003 Resource Kit
      .Run "powershell Start-Sleep -Milliseconds " & Int(sec * 1000), 0, True    ' Windows 10 Powershell
   End With
End Function

'	Function LockFile - https://www.hmailserver.com/forum/viewtopic.php?p=212052
Function LockFile(strPath)
   Const Append = 8
   Const Unicode = -1
   Dim i
   On Error Resume Next
   With CreateObject("Scripting.FileSystemObject")
      For i = 0 To 30
         Err.Clear
         Set LockFile = .OpenTextFile(strPath, Append, True, Unicode)
         If (Not Err.Number = 70) Then Exit For
         Wait(1)
      Next
   End With
   If (Err.Number = 70) Then
      EventLog.Write( "ERROR: EventHandlers.vbs" )
      EventLog.Write( "File " & strPath & " is locked and timeout was exceeded." )
      Err.Clear
   ElseIf (Err.Number <> 0) Then
      EventLog.Write( "ERROR: EventHandlers.vbs : Function LockFile" )
      EventLog.Write( "Error       : " & Err.Number )
      EventLog.Write( "Error (hex) : 0x" & Hex(Err.Number) )
      EventLog.Write( "Source      : " & Err.Source )
      EventLog.Write( "Description : " & Err.Description )
      Err.Clear
   End If
   On Error Goto 0
End Function

'	Function Lookup - https://www.hmailserver.com/forum/viewtopic.php?p=212052
Function Lookup(strRegEx, strMatch) : Lookup = False
   With CreateObject("VBScript.RegExp")
      .Pattern = strRegEx
      .Global = False
      .MultiLine = True
      .IgnoreCase = True
      If .Test(strMatch) Then Lookup = True
   End With
End Function

'	Function IsInSpamHausZEN - http://hmailserver.com/forum/viewtopic.php?f=7&t=34058
Function IsInSpamHausZEN(strIP) : IsInSpamHausZEN = false
	Dim a : a = Split(strIP, ".")
	With CreateObject("DNSLibrary.DNSResolver")
		strIP = .DNSLookup(a(3) & "." & a(2) & "." & a(1) & "." & a(0) & ".zen.spamhaus.org")
	End With
	Dim strRegEx : strRegEx = "(127\.0\.0\.(?:2|3|4|9))"
	IsInSpamHausZEN = Lookup(strRegEx, strIP)
End Function

'	Function GetDatabaseObject - https://www.hmailserver.com/forum/viewtopic.php?p=212052
Function GetDatabaseObject()
   Dim oApp : Set oApp = CreateObject("hMailServer.Application")
   Call oApp.Authenticate(ADMIN, PASSWORD)
   Set GetDatabaseObject = oApp.Database
End Function

'	Function AutoBan - https://www.hmailserver.com/forum/viewtopic.php?p=212052
Function AutoBan(sIPAddress, sReason, iDuration, sType) : AutoBan = False
   '
   '   sType can be one of the following;
   '   "yyyy" Year, "m" Month, "d" Day, "h" Hour, "n" Minute, "s" Second
   '
   Dim oApp : Set oApp = CreateObject("hMailServer.Application")
   Call oApp.Authenticate(ADMIN, PASSWORD)
   With LockFile(TEMPDIR & "\autoban.lck")
      On Error Resume Next
      Dim oSecurityRange : Set oSecurityRange = oApp.Settings.SecurityRanges.ItemByName("(" & sReason & ") " & sIPAddress)
      If Err.Number = 9 Then
         With oApp.Settings.SecurityRanges.Add
            .Name = "(" & sReason & ") " & sIPAddress
            .LowerIP = sIPAddress
            .UpperIP = sIPAddress
            .Priority = 20
            .Expires = True
            .ExpiresTime = DateAdd(sType, iDuration, Now())
            .Save
         End With
         AutoBan = True
      End If
      On Error Goto 0
      .Close
   End With
   Set oApp = Nothing
End Function

'	Function Disconnect - http://hmailserver.com/forum/viewtopic.php?f=7&t=34058
'	Download disconnect.exe from: https://d-fault.nl/files/Disconnect.zip
Function Disconnect(sIPAddress)
	With CreateObject("WScript.Shell")
		.Run """C:\Program Files (x86)\hMailServer\Events\Disconnect.exe"" " & sIPAddress & "", 0, True
		REM EventLog.Write("Disconnect.exe " & sIPAddress & "")
	End With
End Function

'	Function FWBan - http://hmailserver.com/forum/viewtopic.php?f=9&t=34082
Function FWBan(sIPAddress, sReason)
   Include("C:\Program Files (x86)\hMailServer\Events\VbsJson.vbs")
   Dim ReturnCode, Json, oGeoip, oXML
   Set Json = New VbsJson
   On Error Resume Next
   Set oXML = CreateObject ("Msxml2.XMLHTTP.3.0")
   oXML.Open "GET", "http://ip-api.com/json/" & sIPAddress, False
   oXML.Send
   Set oGeoip = Json.Decode(oXML.responseText)
   ReturnCode = oXML.Status
   On Error Goto 0

   Dim strSQL, oDB : Set oDB = GetDatabaseObject
   strSQL = "INSERT INTO hm_FWBan (timestamp,ipaddress,ban_reason,countrycode,country) VALUES (NOW(),'" & sIPAddress & "','" & sReason & "','" & oGeoip("countryCode") & "','" & oGeoip("country") & "');"
   Call oDB.ExecuteSQL(strSQL)
End Function

'******************************************************************************************************************************
'********** hMailServer Triggers                                                                                     **********
'******************************************************************************************************************************

Sub OnClientConnect(oClient)
   
   ' Exclude Backup-MX & local LAN from test
   If (Left(oClient.IPAddress, 12) = "184.105.182.") Then Exit Sub
   If (Left(oClient.IPAddress, 10) = "192.168.1.") Then Exit Sub
   If oClient.IPAddress = "127.0.0.1" Then Exit Sub

   ' Filter out "impatient" servers. Alternative to GreyListing.
   If (oClient.Port = 25) Then Wait(20)

   Dim strPort
   strPort = Trim(Mid("SMTP POP  IMAP SMTPSSUBM IMAPSPOPS ", InStr("25   110  143  465  587  993  995  ", oClient.Port), 5))

   '   Spamhaus Zen detection
   If IsInSpamHausZEN(oClient.IPAddress) Then
      Result.Value = 1
       Call Disconnect(oClient.IPAddress)
       Call FWBan(oClient.IPAddress, "Spamhaus")
       Call AutoBan(oClient.IPAddress, "Spamhaus - " & oClient.IpAddress, 1, "h")
	  EventLog.Write(strPort & " " & oClient.Port & " OnHELO REJECTED zen.spamhaus.org " & oClient.IPAddress)
      Exit Sub
   End If

	'	GEOIP Lookup
   Dim ReturnCode, Json, oGeoip, oXML, strPort, strBase
   Include("C:\Program Files (x86)\hMailServer\Events\VbsJson.vbs")
   Set Json = New VbsJson
   On Error Resume Next
   Set oXML = CreateObject ("Msxml2.XMLHTTP.3.0")
   oXML.Open "GET", "http://ip-api.com/json/" & oClient.IPAddress, False
   oXML.Send
   Set oGeoip = Json.Decode(oXML.responseText)
   ReturnCode = oXML.Status
   On Error Goto 0

   If (ReturnCode <> 200 ) Then
      EventLog.Write("<OnClientConnect.error> ip-api.com lookup failed, error code: " & ReturnCode & " on IP address " & oClient.IPAddress)
      Exit Sub
   End If

   ' ALLOWED COUNTRIES - Port 25 only... Check Alpha-2 Code here -> https://en.wikipedia.org/wiki/ISO_3166-1
   If (oClient.Port = 25) Then
	   strBase = "^(US|CA|AT|BE|CH|CZ|DE|DK|ES|FI|FR|GB|GL|GR|HR|HU|IE|IS|IT|LI|MC|NL|NO|PL|PT|RO|RS|SE|SI|SK|SM|AU|NZ)$"
	   If Lookup(strBase, oGeoip("countryCode")) Then
	   EventLog.Write(strPort & " " & oClient.Port & " OnClientConnect Accepted GeoIP-Lookup " & oClient.IPAddress & " " & oGeoip("countryCode") & " " & oGeoip("country"))
	      Exit Sub
	   End If
   ' Disconnect all others connecting to port 25.
	   Result.Value = 1
       Call Disconnect(oClient.IPAddress)
       Call FWBan(oClient.IPAddress, "GeoIP")
       Call AutoBan(oClient.IPAddress, "GeoIP - " & oClient.IpAddress, 1, "h")
	   EventLog.Write(strPort & " " & oClient.Port & " OnClientConnect REJECTED GeoIP-Lookup " & oClient.IPAddress & " " & oGeoip("countryCode") & " " & oGeoip("country"))
	   Exit Sub
   Else
   ' ALLOWED COUNTRIES - All ports except 25... Check Alpha-2 Code here -> https://en.wikipedia.org/wiki/ISO_3166-1
	   strBase = "^(US)$"
	   If Lookup(strBase, oGeoip("countryCode")) Then
		  EventLog.Write(strPort & " Port " & oClient.Port & vbTab & " Connection accepted by GeoIP Lookup" & vbTab & vbTab & Chr(34) & oClient.IPAddress & Chr(34) & vbTab & Chr(34) & oGeoip("countryCode") & Chr(34) & vbTab & Chr(34) & oGeoip("country"))
		  Exit Sub
	   End If
   ' Disconnect all others connecting to any port except 25.
	   Result.Value = 1
       Call Disconnect(oClient.IPAddress)
       Call FWBan(oClient.IPAddress, "GeoIP")
       Call AutoBan(oClient.IPAddress, "GeoIP - " & oClient.IpAddress, 1, "h")
	   EventLog.Write(strPort & " Port " & oClient.Port & vbTab & " Connection REJECTED by GeoIP Lookup" & vbTab & vbTab & Chr(34) & oClient.IPAddress & Chr(34) & vbTab & Chr(34) & oGeoip("countryCode") & Chr(34) & vbTab & Chr(34) & oGeoip("country"))
	   Exit Sub
   End If
End Sub