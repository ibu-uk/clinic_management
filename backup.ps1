# Set paths
$backupDir = "C:\xampp\htdocs\clinic_management_backup"
$sourceDir = "C:\xampp\htdocs\clinic_management"
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupName = "clinic_management_$timestamp"
$backupPath = Join-Path $backupDir $backupName

# Create backup directory if it doesn't exist
if (!(Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir
}

# Create backup folder
New-Item -ItemType Directory -Path $backupPath

# Copy all project files
Copy-Item -Path "$sourceDir\*" -Destination $backupPath -Recurse

# Export database
$mysqlPath = "C:\xampp\mysql\bin"
$dbUser = "root"
$dbName = "clinic_management"

# Export tables one by one
$tables = @(
    "users",
    "equipment",
    "clinic_records",
    "payments",
    "maintenance",
    "monthly_installments"
)

foreach ($table in $tables) {
    & "$mysqlPath\mysqldump" -u $dbUser $dbName $table > "$backupPath\${table}.sql"
}

# Create zip file
Compress-Archive -Path $backupPath -DestinationPath "$backupPath.zip"

# Remove temporary backup folder
Remove-Item -Path $backupPath -Recurse

Write-Host "Backup completed! Your backup is saved at: $backupPath.zip"
