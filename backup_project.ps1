# Backup script for Clinic Management System
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupDir = "clinic_management_backup_$timestamp"

# Create backup directory
New-Item -ItemType Directory -Path $backupDir | Out-Null

# Function to copy files and maintain directory structure
function Copy-ProjectFiles {
    param (
        [string]$source,
        [string]$destination
    )
    
    # Create destination directory if it doesn't exist
    if (!(Test-Path $destination)) {
        New-Item -ItemType Directory -Path $destination | Out-Null
    }
    
    # Copy all files and directories except node_modules and vendor
    Get-ChildItem -Path $source -Exclude @("node_modules", "vendor", ".git", "*.log") | ForEach-Object {
        if ($_.PSIsContainer) {
            Copy-ProjectFiles $_.FullName "$destination\$($_.Name)"
        } else {
            Copy-Item $_.FullName -Destination $destination
        }
    }
}

# Export database
Write-Host "Exporting database..."
$mysqlDump = "C:\xampp\mysql\bin\mysqldump.exe"
$dbName = "clinic_management"
$dbUser = "root"
$dbPass = ""

# Create database backup
& $mysqlDump --user=$dbUser --no-tablespaces --databases $dbName > "$backupDir\database_backup.sql"

# Copy project files
Write-Host "Copying project files..."
Copy-ProjectFiles "." $backupDir

# Create README file with instructions
$readmeContent = @"
# Clinic Management System Backup

This backup was created on $(Get-Date)

## Restoration Instructions

1. Database Restoration:
   - Create a new database named 'clinic_management' in MySQL
   - Import the database using the following command:
     mysql -u root clinic_management < database_backup.sql

2. Project Files:
   - Copy all contents of this backup folder to your XAMPP htdocs directory
   - The final path should be: C:\xampp\htdocs\clinic_management

3. Configuration:
   - Check config/database.php for database connection settings
   - Update if necessary with your database credentials

4. XAMPP Requirements:
   - PHP version: 7.4 or higher
   - MySQL version: 5.7 or higher
   - Apache with mod_rewrite enabled

5. File Permissions:
   - Ensure the 'uploads' directory is writable
   - Ensure the 'logs' directory is writable

6. Testing:
   - Access the system through: http://localhost/clinic_management
   - Default login credentials (if not changed):
     Username: admin
     Password: admin123

For any issues, please contact the system administrator.
"@

$readmeContent | Out-File -FilePath "$backupDir\RESTORE_INSTRUCTIONS.md" -Encoding UTF8

# Create a zip file of the backup
Write-Host "Creating zip archive..."
Compress-Archive -Path $backupDir\* -DestinationPath "$backupDir.zip"

# Cleanup temporary directory
Remove-Item -Recurse -Force $backupDir

Write-Host "Backup completed successfully!"
Write-Host "Backup file: $backupDir.zip"
Write-Host "Please copy this zip file to transfer the project."
