<?php
require_once 'config/database.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    echo "<h2>Fix Maintenance Schedule</h2>";
    
    // Get all equipment with maintenance schedules
    $query = "SELECT * FROM equipment WHERE maintenance_schedule IS NOT NULL";
    $stmt = $db->query($query);
    $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($equipment as $item) {
        echo "<h3>Fixing maintenance for: " . htmlspecialchars($item['equipment_name']) . "</h3>";
        
        // Delete existing maintenance records
        $delete_query = "DELETE FROM maintenance WHERE equipment_id = ?";
        $stmt = $db->prepare($delete_query);
        $stmt->execute([$item['id']]);
        
        if (!empty($item['maintenance_schedule'])) {
            $schedules = explode(',', $item['maintenance_schedule']);
            $start_date = new DateTime($item['contract_start_date']);
            $end_date = new DateTime($item['contract_end_date']);
            
            // Calculate number of months between start and end date
            $interval = $start_date->diff($end_date);
            $total_months = ($interval->y * 12) + $interval->m;
            
            echo "<p>Contract period: {$total_months} months</p>";
            
            foreach ($schedules as $months) {
                $months = intval($months);
                if ($months <= 0) continue;
                
                // Calculate how many maintenance visits we need
                $num_visits = ceil($total_months / $months);
                $current_date = clone $start_date;
                
                echo "<p>Schedule: Every {$months} months, total visits: {$num_visits}</p>";
                
                for ($i = 0; $i < $num_visits && $current_date <= $end_date; $i++) {
                    $status = $current_date <= new DateTime() ? 'completed' : 'scheduled';
                    
                    $insert_query = "INSERT INTO maintenance 
                                   (equipment_id, maintenance_date, status, created_at) 
                                   VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
                    $stmt = $db->prepare($insert_query);
                    $stmt->execute([$item['id'], $current_date->format('Y-m-d'), $status]);
                    
                    echo "<p>Added maintenance: " . $current_date->format('Y-m-d') . " ({$status})</p>";
                    
                    $current_date->modify("+{$months} months");
                }
            }
        }
        
        // Show current maintenance count
        $count_query = "SELECT 
                       SUM(CASE WHEN status = 'completed' AND maintenance_date <= CURRENT_DATE THEN 1 ELSE 0 END) as completed,
                       SUM(CASE WHEN status = 'scheduled' AND maintenance_date > CURRENT_DATE THEN 1 ELSE 0 END) as scheduled
                       FROM maintenance 
                       WHERE equipment_id = ?";
        $stmt = $db->prepare($count_query);
        $stmt->execute([$item['id']]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p style='color: green;'>Final count - Completed: {$counts['completed']}, Scheduled: {$counts['scheduled']}</p>";
    }
    
    echo "<p><a href='equipment.php'>Return to Equipment List</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
