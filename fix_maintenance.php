<?php
require_once 'config/database.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    echo "<h2>Maintenance System Fix</h2>";
    
    // 1. Check if maintenance_schedule column exists in equipment table
    $check_column = "SHOW COLUMNS FROM equipment LIKE 'maintenance_schedule'";
    $result = $db->query($check_column);
    
    if ($result->rowCount() === 0) {
        echo "<p>Adding maintenance_schedule column to equipment table...</p>";
        $db->exec("ALTER TABLE equipment ADD COLUMN maintenance_schedule VARCHAR(50) DEFAULT NULL AFTER contract_file");
        echo "<p style='color: green;'>✓ Added maintenance_schedule column</p>";
    } else {
        echo "<p style='color: green;'>✓ maintenance_schedule column exists</p>";
    }
    
    // 2. Check if maintenance table exists
    $check_table = "SHOW TABLES LIKE 'maintenance'";
    $result = $db->query($check_table);
    
    if ($result->rowCount() === 0) {
        echo "<p>Creating maintenance table...</p>";
        $create_table = "CREATE TABLE maintenance (
            id INT PRIMARY KEY AUTO_INCREMENT,
            equipment_id INT NOT NULL,
            maintenance_date DATE NOT NULL,
            status ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (equipment_id) REFERENCES equipment(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($create_table);
        echo "<p style='color: green;'>✓ Created maintenance table</p>";
    } else {
        echo "<p style='color: green;'>✓ maintenance table exists</p>";
    }
    
    // 3. Check equipment records and create maintenance schedules if needed
    $equipment_query = "SELECT * FROM equipment WHERE maintenance_schedule IS NOT NULL";
    $stmt = $db->query($equipment_query);
    $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($equipment as $item) {
        echo "<p>Checking maintenance for equipment: " . htmlspecialchars($item['equipment_name']) . "</p>";
        
        // Check if maintenance records exist
        $check_maintenance = "SELECT COUNT(*) FROM maintenance WHERE equipment_id = ?";
        $stmt = $db->prepare($check_maintenance);
        $stmt->execute([$item['id']]);
        $count = $stmt->fetchColumn();
        
        if ($count === 0 && !empty($item['maintenance_schedule'])) {
            echo "<p>Creating maintenance schedule...</p>";
            
            $schedules = explode(',', $item['maintenance_schedule']);
            $start_date = new DateTime($item['contract_start_date']);
            $end_date = new DateTime($item['contract_end_date']);
            
            foreach ($schedules as $months) {
                $current_date = clone $start_date;
                while ($current_date <= $end_date) {
                    $insert_query = "INSERT INTO maintenance 
                                   (equipment_id, maintenance_date, status, created_at) 
                                   VALUES (?, ?, 'scheduled', CURRENT_TIMESTAMP)";
                    $stmt = $db->prepare($insert_query);
                    $stmt->execute([$item['id'], $current_date->format('Y-m-d')]);
                    
                    $current_date->modify("+{$months} months");
                }
            }
            echo "<p style='color: green;'>✓ Created maintenance schedule</p>";
        } else {
            echo "<p style='color: green;'>✓ Maintenance records exist</p>";
        }
    }
    
    echo "<p style='color: green; font-weight: bold;'>Maintenance system check complete!</p>";
    echo "<p><a href='equipment.php'>Return to Equipment List</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
