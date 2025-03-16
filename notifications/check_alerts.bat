@echo off
:: Run this script to check for alerts and show Windows notifications

:: Get PHP response
curl -s http://localhost/clinic_management/notifications/check_alerts.php > alerts.json

:: Parse JSON and show notifications using PowerShell
powershell -ExecutionPolicy Bypass -Command ^
"$alerts = Get-Content 'alerts.json' | ConvertFrom-Json; ^
if ($alerts.status -eq 'success') { ^
    foreach ($alert in $alerts.alerts) { ^
        & '.\show_notifications.ps1' -Title $alert.title -Message $alert.message ^
    } ^
}"

:: Clean up
del alerts.json
