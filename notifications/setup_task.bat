@echo off
:: Setup Windows Task Scheduler to run alerts every hour

:: Get the current directory
set "SCRIPT_PATH=%~dp0check_alerts.bat"

:: Create the scheduled task
SCHTASKS /CREATE /SC HOURLY /TN "ClinicManagement\CheckAlerts" /TR "%SCRIPT_PATH%" /F

echo Task scheduled successfully! You will now receive alerts every hour.
pause
