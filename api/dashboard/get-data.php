<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $currentDate = date('Y-m-d');
    $thirtyDaysFromNow = date('Y-m-d', strtotime('+30 days'));
    
    // Get active equipment count
    $equipmentQuery = "SELECT COUNT(*) as count FROM equipment WHERE status = 'active'";
    $equipmentStmt = $db->query($equipmentQuery);
    $equipmentCount = $equipmentStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get active clinic records count
    $clinicQuery = "SELECT COUNT(*) as count FROM clinic_records WHERE status = 'active'";
    $clinicStmt = $db->query($clinicQuery);
    $clinicCount = $clinicStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get pending payments count (upcoming installments within next 30 days)
    $pendingPaymentsQuery = "SELECT COUNT(*) as count FROM (
        SELECT e.id FROM equipment e 
        WHERE e.status = 'active' 
        AND e.payment_type = 'installment'
        AND e.remaining_amount > 0 
        AND e.next_payment_date BETWEEN :current_date AND :thirty_days
        UNION 
        SELECT cr.id FROM clinic_records cr
        WHERE cr.status = 'active'
        AND cr.payment_type = 'installment'
        AND cr.remaining_amount > 0
        AND cr.next_payment_date BETWEEN :current_date AND :thirty_days
    ) as pending";
    
    $pendingStmt = $db->prepare($pendingPaymentsQuery);
    $pendingStmt->bindParam(':current_date', $currentDate);
    $pendingStmt->bindParam(':thirty_days', $thirtyDaysFromNow);
    $pendingStmt->execute();
    $pendingCount = $pendingStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get expiring contracts count
    $expiringQuery = "SELECT COUNT(*) as count FROM (
        SELECT id FROM equipment 
        WHERE status = 'active' 
        AND contract_end_date BETWEEN :current_date AND :thirty_days
        UNION
        SELECT id FROM clinic_records
        WHERE status = 'active'
        AND contract_end_date BETWEEN :current_date AND :thirty_days
    ) as expiring";
    
    $expiringStmt = $db->prepare($expiringQuery);
    $expiringStmt->bindParam(':current_date', $currentDate);
    $expiringStmt->bindParam(':thirty_days', $thirtyDaysFromNow);
    $expiringStmt->execute();
    $expiringCount = $expiringStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get upcoming payments details
    $upcomingPaymentsQuery = "SELECT 
        'equipment' as type,
        equipment_name as name,
        contract_number,
        next_payment_date,
        monthly_installment as amount
    FROM equipment 
    WHERE status = 'active' 
    AND payment_type = 'installment'
    AND remaining_amount > 0
    AND next_payment_date BETWEEN :current_date AND :thirty_days
    UNION
    SELECT 
        'clinic' as type,
        company_name as name,
        contract_number,
        next_payment_date,
        monthly_payment as amount
    FROM clinic_records
    WHERE status = 'active'
    AND payment_type = 'installment'
    AND remaining_amount > 0
    AND next_payment_date BETWEEN :current_date AND :thirty_days
    ORDER BY next_payment_date ASC
    LIMIT 5";
    
    $upcomingStmt = $db->prepare($upcomingPaymentsQuery);
    $upcomingStmt->bindParam(':current_date', $currentDate);
    $upcomingStmt->bindParam(':thirty_days', $thirtyDaysFromNow);
    $upcomingStmt->execute();
    $upcomingPayments = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get upcoming maintenance
    $maintenanceQuery = "SELECT 
        equipment_name as name,
        contract_number,
        next_maintenance_date,
        maintenance_notes
    FROM equipment 
    WHERE status = 'active' 
    AND next_maintenance_date BETWEEN :current_date AND :thirty_days
    ORDER BY next_maintenance_date ASC
    LIMIT 5";
    
    $maintenanceStmt = $db->prepare($maintenanceQuery);
    $maintenanceStmt->bindParam(':current_date', $currentDate);
    $maintenanceStmt->bindParam(':thirty_days', $thirtyDaysFromNow);
    $maintenanceStmt->execute();
    $upcomingMaintenance = $maintenanceStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'counts' => [
                'equipment' => $equipmentCount,
                'clinic_records' => $clinicCount,
                'pending_payments' => $pendingCount,
                'expiring_contracts' => $expiringCount
            ],
            'upcoming_payments' => $upcomingPayments,
            'upcoming_maintenance' => $upcomingMaintenance
        ]
    ]);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
