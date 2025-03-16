<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $alerts = [];
    
    // Check upcoming maintenance (next 7 days)
    $query = "SELECT m.*, e.equipment_name 
              FROM maintenance m
              JOIN equipment e ON m.equipment_id = e.id
              WHERE m.status = 'scheduled'
              AND m.maintenance_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
              ORDER BY m.maintenance_date ASC";
    $stmt = $db->query($query);
    $maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($maintenance as $m) {
        $alerts[] = [
            'type' => 'maintenance',
            'title' => 'Upcoming Maintenance',
            'message' => "Maintenance due for {$m['equipment_name']} on " . date('Y-m-d', strtotime($m['maintenance_date'])),
            'priority' => 'high'
        ];
    }
    
    // Check upcoming payments (next 7 days)
    $query = "SELECT e.equipment_name, e.monthly_installment,
              DATE_ADD(
                GREATEST(e.contract_start_date, CURDATE()),
                INTERVAL (
                    FLOOR(
                        DATEDIFF(CURDATE(), e.contract_start_date) / 30
                    ) + 1
                ) * 30 DAY
              ) as next_payment_date
              FROM equipment e
              WHERE e.payment_type = 'installment'
              AND e.status = 'active'
              AND e.remaining_amount > 0
              HAVING next_payment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    $stmt = $db->query($query);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($payments as $p) {
        $alerts[] = [
            'type' => 'payment',
            'title' => 'Payment Due',
            'message' => "Payment of {$p['monthly_installment']} KWD due for {$p['equipment_name']} on " . date('Y-m-d', strtotime($p['next_payment_date'])),
            'priority' => 'high'
        ];
    }
    
    // Check contracts expiring (next 30 days)
    $query = "SELECT equipment_name, contract_end_date
              FROM equipment
              WHERE status = 'active'
              AND contract_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
              ORDER BY contract_end_date ASC";
    $stmt = $db->query($query);
    $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($contracts as $c) {
        $alerts[] = [
            'type' => 'contract',
            'title' => 'Contract Expiring',
            'message' => "Contract for {$c['equipment_name']} expires on " . date('Y-m-d', strtotime($c['contract_end_date'])),
            'priority' => 'medium'
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'alerts' => $alerts
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
