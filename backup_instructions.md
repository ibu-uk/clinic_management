# Clinic Management System - Backup & Restoration Instructions

## PHP 7 Compatibility Requirements

1. **Required PHP Version**
   - Minimum: PHP 7.2
   - Recommended: PHP 7.4
   - Current system: PHP 8.0.30

2. **Required PHP Extensions**
   - mysqli
   - pdo
   - pdo_mysql
   - json
   - gd
   - mbstring

3. **XAMPP Version**
   - Use XAMPP version compatible with PHP 7.x
   - Recommended: XAMPP 7.4.x

## Pre-Migration Steps

1. **Check Compatibility**
   ```
   http://localhost/clinic_management/php7_compatibility_check.php
   ```
   This will scan for any PHP 8 specific features that need to be adjusted.

2. **PHP Configuration**
   - Open php.ini in XAMPP
   - Enable required extensions
   - Set appropriate memory limits
   - Enable error reporting during testing

## Files to Backup

1. **Project Files**
   - Entire `clinic_management` folder from XAMPP's htdocs directory
   - Location: `C:\xampp\htdocs\clinic_management`

2. **Database Backup**
   - Export the database using phpMyAdmin
   - Database name: `clinic_management`

## Backup Steps

1. **Export Database:**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Select "clinic_management" database
   - Click "Export" at the top menu
   - Choose "Custom" export method
   - Under "Format-specific options":
     - Uncheck "CREATE VIEW" option
     - Check "Add DROP TABLE" option
     - Check "Add CREATE DATABASE" option
   - Click "Go" to download `clinic_management.sql`

2. **Copy Project Files:**
   - Create a new folder named "clinic_backup"
   - Copy these folders:
     - api/
     - assets/
     - config/
     - includes/
     - uploads/
     - All PHP files

## Restoration Steps

1. **XAMPP Installation:**
   - Download XAMPP with PHP 7.4 from Apache Friends website
   - Install XAMPP
   - Start Apache and MySQL services

2. **Database Restoration:**
   ```sql
   CREATE DATABASE IF NOT EXISTS clinic_management;
   USE clinic_management;
   -- Import using phpMyAdmin or command line:
   -- mysql -u root clinic_management < clinic_management.sql
   ```

3. **Project Files:**
   - Copy the entire `clinic_management` folder to:
     `C:\xampp\htdocs\` on the target PC

4. **PHP Configuration:**
   - Open `C:\xampp\php\php.ini`
   - Enable these extensions:
     ```ini
     extension=mysqli
     extension=pdo_mysql
     extension=gd
     extension=mbstring
     ```

5. **Configuration Check:**
   - Run compatibility check:
     `http://localhost/clinic_management/php7_compatibility_check.php`
   - Run installation check:
     `http://localhost/clinic_management/check_installation.php`
   - Verify database connection in `config/database.php`

6. **Directory Permissions:**
   - Right-click on `uploads/` folder
   - Properties → Security → Edit
   - Add "Everyone" with Full Control (for testing)
   - Do the same for `logs/` folder

## Testing After Installation

1. **Basic Functionality:**
   - Open `http://localhost/clinic_management`
   - Log in with credentials
   - Test each menu item

2. **Critical Features:**
   - Equipment management
   - Maintenance scheduling
   - Payment processing
   - Report generation
   - File uploads
   - PDF generation

3. **Error Checking:**
   - Check XAMPP error logs:
     - `C:\xampp\apache\logs\error.log`
     - `C:\xampp\php\logs\php_error_log`

## Common Issues & Solutions

1. **Database Connection Errors:**
   - Verify MySQL service is running
   - Check credentials in `config/database.php`
   - Try `localhost` or `127.0.0.1` for host

2. **File Upload Issues:**
   - Check folder permissions
   - Verify PHP upload settings in php.ini
   - Check upload_max_filesize and post_max_size

3. **Session Problems:**
   - Clear browser cache
   - Check session.save_path in php.ini
   - Verify session directory permissions

4. **Display Errors:**
   - In development, enable in php.ini:
     ```ini
     display_errors = On
     error_reporting = E_ALL
     ```

## Security Notes

1. **After Testing:**
   - Remove "Everyone" permission from folders
   - Set proper file permissions
   - Disable display_errors in php.ini
   - Update default passwords

2. **Important Settings:**
   - Use strong database passwords
   - Secure the config directory
   - Keep error logging enabled
   - Regular backup schedule

For any issues during restoration, check the error logs or contact system administrator.
