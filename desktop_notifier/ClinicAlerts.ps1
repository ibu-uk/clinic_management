# PowerShell script for Clinic Management Alerts
# This can be run on any Windows PC without XAMPP

param (
    [string]$ServerUrl = "http://YOUR_SERVER_IP/clinic_management"  # Replace with your actual server IP
)

# Function to show Windows notification
function Show-Notification {
    param (
        [string]$Title,
        [string]$Message,
        [string]$Priority = "normal"
    )

    $icon = switch ($Priority) {
        "critical" { "Error" }
        "high" { "Warning" }
        default { "Information" }
    }

    [Windows.UI.Notifications.ToastNotificationManager, Windows.UI.Notifications, ContentType = WindowsRuntime] | Out-Null
    [Windows.UI.Notifications.ToastNotification, Windows.UI.Notifications, ContentType = WindowsRuntime] | Out-Null
    [Windows.Data.Xml.Dom.XmlDocument, Windows.Data.Xml.Dom.XmlDocument, ContentType = WindowsRuntime] | Out-Null

    $APP_ID = "ClinicManagement"

    $template = @"
    <toast>
        <visual>
            <binding template="ToastText02">
                <text id="1">$Title</text>
                <text id="2">$Message</text>
            </binding>
        </visual>
    </toast>
"@

    $xml = New-Object Windows.Data.Xml.Dom.XmlDocument
    $xml.LoadXml($template)
    $toast = New-Object Windows.UI.Notifications.ToastNotification $xml
    [Windows.UI.Notifications.ToastNotificationManager]::CreateToastNotifier($APP_ID).Show($toast)
}

# Function to check alerts
function Check-Alerts {
    try {
        # Get alerts from server
        $response = Invoke-RestMethod -Uri "$ServerUrl/api/alerts/get_alerts.php" -Method Get

        if ($response.status -eq "success") {
            $alerts = $response.alerts

            foreach ($alert in $alerts) {
                # Show notification based on priority
                if ($alert.priority -eq "critical") {
                    Show-Notification -Title $alert.title -Message $alert.message -Priority "critical"
                }
                elseif ($alert.priority -eq "high" -and (Get-Date).Hour -ge 9) {
                    # Show high priority alerts only during business hours
                    Show-Notification -Title $alert.title -Message $alert.message -Priority "high"
                }
                elseif ((Get-Date).Hour -eq 9) {
                    # Show medium priority alerts only at start of day
                    Show-Notification -Title $alert.title -Message $alert.message -Priority "normal"
                }
            }
        }
    }
    catch {
        Show-Notification -Title "Error" -Message "Could not connect to server. Please check your connection." -Priority "critical"
    }
}

# Main loop
while ($true) {
    Check-Alerts
    Start-Sleep -Seconds 3600  # Check every hour
}
