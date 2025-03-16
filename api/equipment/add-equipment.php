<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();

    // Validate contract number uniqueness for the specific contract type
    $check_query = "SELECT id FROM equipment WHERE contract_number = ? AND contract_type = ?";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$_POST['contract_number'], $_POST['contract_type']]);
    
    if ($stmt->rowCount() > 0) {
        throw new Exception('Contract number already exists for this contract type');
    }

    // Start transaction
    $db->beginTransaction();

    try {
        // Handle file upload
        $contract_file = null;
        if (isset($_FILES['contract_file']) && $_FILES['contract_file']['size'] > 0) {
            $target_dir = "../../uploads/contracts/";
            $file_extension = strtolower(pathinfo($_FILES["contract_file"]["name"], PATHINFO_EXTENSION));
            $allowed_types = array('pdf', 'doc', 'docx');
            
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception('Invalid file type. Only PDF, DOC, and DOCX files are allowed.');
            }

            $contract_file = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $contract_file;

            if (!move_uploaded_file($_FILES["contract_file"]["tmp_name"], $target_file)) {
                throw new Exception('Failed to upload file');
            }
        }

        // Prepare maintenance schedule
        $maintenance_schedule = isset($_POST['maintenance']) ? implode(',', $_POST['maintenance']) : '';

        // Calculate monthly installment for installment type
        $monthly_installment = 0;
        $down_payment = 0;
        $num_installments = 12;
        $remaining_amount = floatval($_POST['total_cost']); // Initialize remaining amount as total cost

        if ($_POST['payment_type'] === 'installment') {
            $down_payment = floatval($_POST['down_payment']);
            $num_installments = intval($_POST['num_installments']) ?: 12;
            $total_after_down = floatval($_POST['total_cost']) - $down_payment;
            $monthly_installment = $total_after_down / $num_installments;
            $remaining_amount = $total_after_down; // For installment, remaining is total minus down payment
            
            // Calculate installment start date based on months selected
            $start_months = intval($_POST['installment_start_month']) ?: 1;
            $contract_start = new DateTime($_POST['contract_start_date']);
            $installment_start = clone $contract_start;
            $installment_start->modify("+{$start_months} months");
            $installment_start_date = $installment_start->format('Y-m-d');
        }

        // Insert equipment record
        $insert_query = "INSERT INTO equipment (
            contract_type, equipment_name, equipment_model, company_name,
            contract_number, contact_number, contract_start_date, contract_end_date,
            payment_type, total_cost, down_payment, num_installments,
            monthly_installment, maintenance_schedule, contract_file, remaining_amount,
            installment_start_date, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";

        $stmt = $db->prepare($insert_query);
        $stmt->execute([
            $_POST['contract_type'],
            $_POST['equipment_name'],
            $_POST['equipment_model'],
            $_POST['company_name'],
            $_POST['contract_number'],
            $_POST['contact_number'],
            $_POST['contract_start_date'],
            $_POST['contract_end_date'],
            $_POST['payment_type'],
            $_POST['total_cost'],
            $down_payment,
            $num_installments,
            $monthly_installment,
            $maintenance_schedule,
            $contract_file,
            $remaining_amount,
            $installment_start_date ?? $_POST['contract_start_date']
        ]);

        $equipment_id = $db->lastInsertId();

        // Create maintenance schedule if selected
        if (!empty($maintenance_schedule)) {
            $schedules = explode(',', $maintenance_schedule);
            $start_date = new DateTime($_POST['contract_start_date']);
            $end_date = new DateTime($_POST['contract_end_date']);
            
            // Calculate number of months between start and end date
            $interval = $start_date->diff($end_date);
            $total_months = ($interval->y * 12) + $interval->m + 1; // Add 1 to include both start and end months
            
            foreach ($schedules as $months) {
                $months = intval($months);
                if ($months <= 0) continue;
                
                // Calculate how many maintenance visits we need
                $num_visits = ceil($total_months / $months);
                $current_date = clone $start_date;
                
                for ($i = 0; $i < $num_visits && $current_date <= $end_date; $i++) {
                    $insert_query = "INSERT INTO maintenance 
                                   (equipment_id, maintenance_date, status, created_at) 
                                   VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
                    $stmt = $db->prepare($insert_query);
                    
                    // Set status based on date
                    $status = $current_date <= new DateTime() ? 'completed' : 'scheduled';
                    
                    $stmt->execute([$equipment_id, $current_date->format('Y-m-d'), $status]);
                    $current_date->modify("+{$months} months");
                }
            }
        }

        $db->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Equipment added successfully',
            'equipment_id' => $equipment_id
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error adding equipment: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
