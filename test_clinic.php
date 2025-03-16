<?php
require_once 'config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Test query 1: Check if table exists
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Available Tables:</h3>";
    print_r($tables);
    
    // Test query 2: Get clinic records count
    $count = $db->query("SELECT COUNT(*) as count FROM clinic_records")->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Total Clinic Records:</h3>";
    print_r($count);
    
    // Test query 3: Get sample records
    $records = $db->query("SELECT * FROM clinic_records LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Sample Records:</h3>";
    print_r($records);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
