<?php
require_once '../../config/database.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $alerts = [];
    
    // Equipment maintenance alerts
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
            'title' => 'Equipment Maintenance Due',
            'message' => "Maintenance due for {$m['equipment_name']} on " . date('Y-m-d', strtotime($m['maintenance_date'])),
            'priority' => 'high'
        ];
    }
    
    // Equipment payment alerts
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
    $equipment_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($equipment_payments as $p) {
        $alerts[] = [
            'type' => 'payment',
            'title' => 'Equipment Payment Due',
            'message' => "Payment of {$p['monthly_installment']} KWD due for {$p['equipment_name']} on " . date('Y-m-d', strtotime($p['next_payment_date'])),
            'priority' => 'high'
        ];
    }
    
    // Clinic Records Payment Alerts
    $query = "SELECT 
        record_type,
        company_name,
        monthly_installment,
        next_payment_date
    FROM clinic_records 
    WHERE payment_type = 'installment'
    AND remaining_amount > 0
    AND next_payment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY next_payment_date ASC";
    
    $stmt = $db->query($query);
    $clinic_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($clinic_payments as $p) {
        $alerts[] = [
            'type' => 'payment',
            'title' => "{$p['record_type']} Payment Due",
            'message' => "Payment of {$p['monthly_installment']} KWD due for {$p['company_name']} on " . date('Y-m-d', strtotime($p['next_payment_date'])),
            'priority' => 'high'
        ];
    }
    
    // Clinic Records Renewal Alerts
    $query = "SELECT 
        record_type,
        company_name,
        expiry_date,
        DATEDIFF(expiry_date, CURDATE()) as days_remaining
    FROM clinic_records
    WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY expiry_date ASC";
    
    $stmt = $db->query($query);
    $clinic_renewals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($clinic_renewals as $r) {
        $priority = $r['days_remaining'] <= 7 ? 'critical' : 'high';
        $alerts[] = [
            'type' => 'renewal',
            'title' => "{$r['record_type']} Renewal Required",
            'message' => "{$r['record_type']} for {$r['company_name']} expires in {$r['days_remaining']} days on " . date('Y-m-d', strtotime($r['expiry_date'])),
            'priority' => $priority
        ];
    }
    
    // Equipment Contract Alerts
    $query = "SELECT equipment_name, contract_end_date,
              DATEDIFF(contract_end_date, CURDATE()) as days_remaining
              FROM equipment
              WHERE status = 'active'
              AND contract_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
              ORDER BY contract_end_date ASC";
    $stmt = $db->query($query);
    $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($contracts as $c) {
        $priority = $c['days_remaining'] <= 7 ? 'critical' : 'high';
        $alerts[] = [
            'type' => 'contract',
            'title' => 'Equipment Contract Expiring',
            'message' => "Contract for {$c['equipment_name']} expires in {$c['days_remaining']} days on " . date('Y-m-d', strtotime($c['contract_end_date'])),
            'priority' => $priority
        ];
    }
    
    // Sort alerts by priority
    usort($alerts, function($a, $b) {
        $priority_order = ['critical' => 1, 'high' => 2, 'medium' => 3];
        return $priority_order[$a['priority']] - $priority_order[$b['priority']];
    });
    
    echo json_encode([
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'alerts' => $alerts
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
