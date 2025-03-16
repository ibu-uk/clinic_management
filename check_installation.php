<?php
echo "<h1>Clinic Management System - Installation Check</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
</style>";

// Check PHP version
echo "<h2>1. PHP Version Check</h2>";
$phpVersion = phpversion();
$requiredPhpVersion = "7.4.0";
if (version_compare($phpVersion, $requiredPhpVersion, '>=')) {
    echo "<p class='success'>✓ PHP Version: {$phpVersion} (OK)</p>";
} else {
    echo "<p class='error'>✗ PHP Version: {$phpVersion} (Required: >= {$requiredPhpVersion})</p>";
}

// Check required PHP extensions
echo "<h2>2. PHP Extensions Check</h2>";
$requiredExtensions = ['mysqli', 'pdo', 'pdo_mysql', 'json', 'gd'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✓ {$ext} extension is loaded</p>";
    } else {
        echo "<p class='error'>✗ {$ext} extension is missing</p>";
    }
}

// Check database connection
echo "<h2>3. Database Connection Check</h2>";
require_once 'config/database.php';
try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    echo "<p class='success'>✓ Database connection successful</p>";
    
    // Check if all required tables exist
    $requiredTables = ['users', 'equipment', 'maintenance', 'clinic_records', 'payments'];
    $query = "SHOW TABLES";
    $stmt = $db->query($query);
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($requiredTables as $table) {
        if (in_array($table, $existingTables)) {
            echo "<p class='success'>✓ Table '{$table}' exists</p>";
        } else {
            echo "<p class='error'>✗ Table '{$table}' is missing</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

// Check directory permissions
echo "<h2>4. Directory Permissions Check</h2>";
$directories = ['uploads', 'logs'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "<p class='success'>✓ '{$dir}' directory is writable</p>";
        } else {
            echo "<p class='error'>✗ '{$dir}' directory is not writable</p>";
        }
    } else {
        echo "<p class='error'>✗ '{$dir}' directory does not exist</p>";
    }
}

// Check configuration file
echo "<h2>5. Configuration File Check</h2>";
if (file_exists('config/database.php')) {
    echo "<p class='success'>✓ Database configuration file exists</p>";
} else {
    echo "<p class='error'>✗ Database configuration file is missing</p>";
}

echo "<br><p>If you see any errors above, please refer to the RESTORE_INSTRUCTIONS.md file for troubleshooting.</p>";
?>
