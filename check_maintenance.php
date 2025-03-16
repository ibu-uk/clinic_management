<?php
require_once 'config/database.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    echo "<h2>Maintenance Records Check</h2>";
    
    // 1. Check maintenance records
    $query = "SELECT m.*, e.equipment_name 
             FROM maintenance m 
             JOIN equipment e ON m.equipment_id = e.id";
    $stmt = $db->query($query);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($records) . " maintenance records.</p>";
    
    if (count($records) === 0) {
        // Get equipment with maintenance schedules
        $eq_query = "SELECT * FROM equipment WHERE maintenance_schedule IS NOT NULL";
        $stmt = $db->query($eq_query);
        $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Found " . count($equipment) . " equipment with maintenance schedules.</p>";
        
        foreach ($equipment as $item) {
            echo "<p>Creating maintenance for: " . htmlspecialchars($item['equipment_name']) . "</p>";
            
            // Create some test maintenance records
            $schedules = ['3', '6', '12'];
            $start_date = new DateTime($item['contract_start_date']);
            $end_date = new DateTime($item['contract_end_date']);
            
            foreach ($schedules as $months) {
                $current_date = clone $start_date;
                while ($current_date <= $end_date) {
                    // Add some completed records for past dates
                    $status = $current_date <= new DateTime() ? 'completed' : 'scheduled';
                    
                    $insert_query = "INSERT INTO maintenance 
                                   (equipment_id, maintenance_date, status, created_at) 
                                   VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
                    $stmt = $db->prepare($insert_query);
                    $stmt->execute([$item['id'], $current_date->format('Y-m-d'), $status]);
                    
                    $current_date->modify("+{$months} months");
                }
            }
            
            echo "<p style='color: green;'>✓ Created maintenance records</p>";
        }
    } else {
        echo "<h3>Existing Records:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Equipment</th><th>Date</th><th>Status</th></tr>";
        foreach ($records as $record) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($record['equipment_name']) . "</td>";
            echo "<td>" . $record['maintenance_date'] . "</td>";
            echo "<td>" . $record['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 2. Verify maintenance counts in equipment list query
    echo "<h3>Maintenance Counts:</h3>";
    $count_query = "SELECT e.equipment_name,
                   (SELECT COUNT(*) FROM maintenance m 
                    WHERE m.equipment_id = e.id 
                    AND m.status = 'completed'
                    AND m.maintenance_date <= CURRENT_DATE) as completed,
                   (SELECT COUNT(*) FROM maintenance m 
                    WHERE m.equipment_id = e.id 
                    AND m.status = 'scheduled'
                    AND m.maintenance_date > CURRENT_DATE) as scheduled
                   FROM equipment e";
    $stmt = $db->query($count_query);
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Equipment</th><th>Completed</th><th>Scheduled</th></tr>";
    foreach ($counts as $count) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($count['equipment_name']) . "</td>";
        echo "<td>" . $count['completed'] . "</td>";
        echo "<td>" . $count['scheduled'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><a href='equipment.php'>Return to Equipment List</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
