<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';
require_once '../../includes/config.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Test equipment query
    $query = "SELECT COUNT(*) as count FROM equipment";
    $stmt = $conn->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Database connection successful!\n";
    echo "Number of equipment records: " . $result['count'] . "\n";
    
    // Test clinic records query
    $query = "SELECT COUNT(*) as count FROM clinic_records";
    $stmt = $conn->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Number of clinic records: " . $result['count'] . "\n";
    
    // Test payments query
    $query = "SELECT COUNT(*) as count FROM payments";
    $stmt = $conn->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Number of payment records: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString();
}
