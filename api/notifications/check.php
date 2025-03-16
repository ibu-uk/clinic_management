<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$database = Database::getInstance();
$db = $database->getConnection();

try {
    $notifications = [];
    $reminder_days = 10; // Days before expiry to start showing notifications
    
    // Check equipment records
    $query = "SELECT * FROM equipment 
              WHERE contract_end_date BETWEEN CURDATE() 
              AND DATE_ADD(CURDATE(), INTERVAL :reminder_days DAY)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':reminder_days' => $reminder_days]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $days_remaining = (new DateTime($row['contract_end_date']))->diff(new DateTime())->days;
        $notifications[] = [
            'type' => 'Equipment',
            'message' => "Equipment contract for '{$row['equipment_name']}' will expire in {$days_remaining} days!"
        ];
    }
    
    // Check clinic records
    $query = "SELECT * FROM clinic_records 
              WHERE expiry_date BETWEEN CURDATE() 
              AND DATE_ADD(CURDATE(), INTERVAL :reminder_days DAY)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':reminder_days' => $reminder_days]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $days_remaining = (new DateTime($row['expiry_date']))->diff(new DateTime())->days;
        $notifications[] = [
            'type' => $row['record_type'],
            'message' => "{$row['record_type']} record will expire in {$days_remaining} days!"
        ];
    }
    
    // Store notifications in database
    if (!empty($notifications)) {
        $insert_query = "INSERT INTO notifications (record_type, record_id, message) 
                        VALUES (:record_type, :record_id, :message)";
        $insert_stmt = $db->prepare($insert_query);
        
        foreach ($notifications as $notification) {
            $insert_stmt->execute([
                ':record_type' => $notification['type'],
                ':record_id' => 0, // You might want to store the actual record ID
                ':message' => $notification['message']
            ]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
