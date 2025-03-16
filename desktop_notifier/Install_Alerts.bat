@echo off
echo Setting up Clinic Management Alerts...

REM Create shortcut in startup folder
powershell -Command "$WshShell = New-Object -comObject WScript.Shell; $Shortcut = $WshShell.CreateShortcut('%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\ClinicAlerts.lnk'); $Shortcut.TargetPath = 'powershell.exe'; $Shortcut.Arguments = '-ExecutionPolicy Bypass -WindowStyle Hidden -File ""%~dp0ClinicAlerts.ps1""'; $Shortcut.Save()"

REM Start the alerts immediately
start /MIN powershell -ExecutionPolicy Bypass -WindowStyle Hidden -File "%~dp0ClinicAlerts.ps1"

echo Setup complete! Alerts will now run automatically when Windows starts.
echo You can find the shortcut in your Windows startup folder.
pause
