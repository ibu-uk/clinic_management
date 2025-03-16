<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('Invalid equipment ID');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();

    // Start transaction
    $db->beginTransaction();

    try {
        // Get existing equipment data
        $query = "SELECT * FROM equipment WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_POST['id']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            throw new Exception('Equipment not found');
        }

        // Handle file upload
        $contract_file = $existing['contract_file']; // Keep existing file by default
        if (isset($_FILES['contract_file']) && $_FILES['contract_file']['size'] > 0) {
            $target_dir = "../../uploads/contracts/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES["contract_file"]["name"], PATHINFO_EXTENSION));
            $allowed_types = array('pdf', 'doc', 'docx');
            
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception('Invalid file type. Only PDF, DOC, and DOCX files are allowed.');
            }

            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["contract_file"]["tmp_name"], $target_file)) {
                // Delete old file if exists
                if ($existing['contract_file'] && file_exists("../../uploads/contracts/" . $existing['contract_file'])) {
                    unlink("../../uploads/contracts/" . $existing['contract_file']);
                }
                $contract_file = $new_filename;
            } else {
                throw new Exception('Failed to upload file');
            }
        }

        // Prepare maintenance schedule
        $maintenance_schedule = isset($_POST['maintenance']) ? implode(',', $_POST['maintenance']) : '';

        // Update equipment
        $update_query = "UPDATE equipment SET 
            contract_type = ?,
            equipment_name = ?,
            equipment_model = ?,
            company_name = ?,
            contract_number = ?,
            contact_number = ?,
            contract_start_date = ?,
            contract_end_date = ?,
            payment_type = ?,
            total_cost = ?,
            down_payment = ?,
            num_installments = ?,
            monthly_installment = ?,
            maintenance_schedule = ?,
            contract_file = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";

        $monthly_installment = 0;
        if ($_POST['payment_type'] === 'installment') {
            $total_after_down = floatval($_POST['total_cost']) - floatval($_POST['down_payment']);
            $num_installments = intval($_POST['num_installments']) ?: 12;
            $monthly_installment = $total_after_down / $num_installments;
        }

        $stmt = $db->prepare($update_query);
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
            $_POST['down_payment'] ?? 0,
            $_POST['num_installments'] ?? 12,
            $monthly_installment,
            $maintenance_schedule,
            $contract_file,
            $_POST['id']
        ]);

        // Update maintenance schedule if changed
        if ($maintenance_schedule !== ($existing['maintenance_schedule'] ?? '')) {
            // Delete future maintenance records
            $delete_query = "DELETE FROM maintenance 
                           WHERE equipment_id = ? 
                           AND maintenance_date > CURRENT_DATE 
                           AND status = 'scheduled'";
            $stmt = $db->prepare($delete_query);
            $stmt->execute([$_POST['id']]);

            // Create new maintenance schedule
            if (!empty($maintenance_schedule)) {
                $schedules = explode(',', $maintenance_schedule);
                $start_date = new DateTime($_POST['contract_start_date']);
                $end_date = new DateTime($_POST['contract_end_date']);

                foreach ($schedules as $months) {
                    $current_date = clone $start_date;
                    while ($current_date <= $end_date) {
                        $insert_query = "INSERT INTO maintenance 
                                       (equipment_id, maintenance_date, status, created_at) 
                                       VALUES (?, ?, 'scheduled', CURRENT_TIMESTAMP)";
                        $stmt = $db->prepare($insert_query);
                        $stmt->execute([$_POST['id'], $current_date->format('Y-m-d')]);
                        
                        $current_date->modify("+{$months} months");
                    }
                }
            }
        }

        $db->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Equipment updated successfully'
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error updating equipment: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
