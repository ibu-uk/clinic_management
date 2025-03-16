<?php
require_once 'config/database.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get equipment record
    $query = "SELECT * FROM equipment WHERE contract_type = 'renew' AND total_cost = 8500 ORDER BY id DESC LIMIT 1";
    $stmt = $db->query($query);
    $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($equipment) {
        echo "Equipment Details:\n";
        print_r($equipment);
        
        // Check payments
        $query = "SELECT SUM(amount) as total_paid FROM payments WHERE record_type = 'equipment' AND record_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$equipment['id']]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\n\nPayments:\n";
        echo "Total Paid: " . ($payment['total_paid'] ?? 0) . "\n";
    } else {
        echo "Equipment record not found";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
