Function IsMySQL() : IsMySQL = False
	If ConfigIni.GetKeyValue("hMailServer","DatabaseType") = "MySQL" Then
		IsMySQL = True
	End If
End Function

Function IsMSSQL() : IsMSSQL = False
	If ConfigIni.GetKeyValue("hMailServer","DatabaseType") = "MSSQL" Then
		IsMSSQL = True
	End If
End Function

Function DBGetCurrentDateTime() 
    DBGetCurrentDateTime = ""
    If (IsMySQL() = True) Then
        DBGetCurrentDateTime = "NOW()"
    Elseif (IsMSSQL() = True) Then
        DBGetCurrentDateTime = "GETDATE()"
    End if
End Function