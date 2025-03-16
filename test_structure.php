<?php
require_once 'config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get table structure
    $structure = $db->query("DESCRIBE clinic_records")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Clinic Records Table Structure:</h3>";
    echo "<pre>";
    print_r($structure);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
