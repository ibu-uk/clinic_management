<?php
// Enable error logging to file
ini_set('log_errors', 1);
ini_set('error_log', '../../logs/php-error.log');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set JSON header first
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Log incoming request data
    error_log("Received POST data: " . print_r($_POST, true));
    error_log("Received FILES data: " . print_r($_FILES, true));

    // Include database after error handling setup
    require_once '../../config/database.php';
    
    // Get database connection
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Get form data
    $data = $_POST;
    
    // Validate required fields
    $required_fields = ['equipment_name', 'equipment_model', 'company_name', 'contract_number', 
                       'contact_number', 'contract_type', 'contract_start_date', 'contract_end_date',
                       'total_cost', 'payment_type'];
                       
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: " . $field);
        }
    }

    // Start transaction
    $db->beginTransaction();
    
    try {
        // Check for duplicate contract number
        $check_query = "SELECT COUNT(*) FROM equipment WHERE contract_number = :contract_number";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':contract_number', $data['contract_number']);
        $check_stmt->execute();
        
        if ($check_stmt->fetchColumn() > 0) {
            throw new Exception("Contract number already exists");
        }

        // Handle file upload if present
        $contract_file = null;
        if (!empty($_FILES['contract_file']['name'])) {
            $target_dir = "../../uploads/contracts/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['contract_file']['name'], PATHINFO_EXTENSION);
            $contract_file = uniqid() . '_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $contract_file;
            
            if (!move_uploaded_file($_FILES['contract_file']['tmp_name'], $target_file)) {
                throw new Exception("Error uploading file");
            }
        }

        // Calculate payment details
        $down_payment = 0;
        $remaining_amount = 0;
        $monthly_installment = 0;
        $num_installments = null;
        
        if ($data['payment_type'] === 'installment') {
            $down_payment = !empty($data['down_payment']) ? floatval($data['down_payment']) : 0;
            $num_installments = !empty($data['num_installments']) ? intval($data['num_installments']) : 12;
            $remaining_amount = floatval($data['total_cost']) - $down_payment;
            $monthly_installment = $remaining_amount / $num_installments;
        }

        // Log calculated values
        error_log("Payment details: " . print_r([
            'payment_type' => $data['payment_type'],
            'total_cost' => $data['total_cost'],
            'down_payment' => $down_payment,
            'remaining_amount' => $remaining_amount,
            'monthly_installment' => $monthly_installment,
            'num_installments' => $num_installments
        ], true));

        // Insert equipment record
        $query = "INSERT INTO equipment (
            equipment_name, equipment_model, company_name, contract_number,
            contact_number, contract_type, contract_start_date, contract_end_date,
            total_cost, payment_type, down_payment, remaining_amount,
            monthly_installment, num_installments, maintenance_schedule, 
            contract_file, status
        ) VALUES (
            :equipment_name, :equipment_model, :company_name, :contract_number,
            :contact_number, :contract_type, :contract_start_date, :contract_end_date,
            :total_cost, :payment_type, :down_payment, :remaining_amount,
            :monthly_installment, :num_installments, :maintenance_schedule,
            :contract_file, 'active'
        )";

        $stmt = $db->prepare($query);
        
        // Bind all parameters
        $params = [
            ':equipment_name' => $data['equipment_name'],
            ':equipment_model' => $data['equipment_model'],
            ':company_name' => $data['company_name'],
            ':contract_number' => $data['contract_number'],
            ':contact_number' => $data['contact_number'],
            ':contract_type' => $data['contract_type'],
            ':contract_start_date' => $data['contract_start_date'],
            ':contract_end_date' => $data['contract_end_date'],
            ':total_cost' => $data['total_cost'],
            ':payment_type' => $data['payment_type'],
            ':down_payment' => $down_payment,
            ':remaining_amount' => $remaining_amount,
            ':monthly_installment' => $monthly_installment,
            ':num_installments' => $num_installments,
            ':maintenance_schedule' => isset($data['maintenance']) ? implode(',', $data['maintenance']) : null,
            ':contract_file' => $contract_file
        ];
        
        // Log query and parameters
        error_log("SQL Query: " . $query);
        error_log("Query Parameters: " . print_r($params, true));

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        if (!$stmt->execute()) {
            $error = $stmt->errorInfo();
            throw new Exception("Failed to save equipment record: " . $error[2]);
        }

        // Commit transaction
        $db->commit();

        // Return success response
        $response = [
            'success' => true,
            'message' => 'Equipment record created successfully'
        ];
        error_log("Sending response: " . print_r($response, true));
        echo json_encode($response);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    // Log error
    error_log("Error creating equipment: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    http_response_code(400);
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    error_log("Sending error response: " . print_r($response, true));
    echo json_encode($response);
}
