<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASEPATH', dirname(dirname(dirname(__FILE__))));
require_once BASEPATH . '/config/database.php';

header('Content-Type: text/plain');

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "Database connection successful!\n\n";
    
    // Check tables
    $tables = ['equipment', 'maintenance', 'companies', 'clinic_records', 'payments'];
    
    foreach ($tables as $table) {
        try {
            $query = "DESCRIBE `$table`";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "Table '$table' exists with " . count($columns) . " columns:\n";
            foreach ($columns as $column) {
                echo "- {$column['Field']} ({$column['Type']})\n";
            }
            
            // Get row count
            $stmt = $conn->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "Total rows: $count\n\n";
            
        } catch (PDOException $e) {
            echo "Error checking table '$table': " . $e->getMessage() . "\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString();
}
