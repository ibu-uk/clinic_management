<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log access to this file
error_log("Reports API accessed: " . date('Y-m-d H:i:s'));

// Set the content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

try {
    // Define base path
    define('BASEPATH', dirname(dirname(dirname(__FILE__))));
    
    error_log("Base path: " . BASEPATH);
    
    // Include necessary files
    require_once BASEPATH . '/config/database.php';
    
    error_log("Database config included");
    
    // Get database connection
    $database = Database::getInstance();
    if (!$database) {
        throw new Exception("Failed to get database instance");
    }
    
    error_log("Database instance created");
    
    $conn = $database->getConnection();
    if (!$conn) {
        throw new Exception("Failed to get database connection");
    }
    
    error_log("Database connection established");

    // Get and validate parameters
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    $startDate = !empty($_GET['startDate']) ? $_GET['startDate'] : null;
    $endDate = !empty($_GET['endDate']) ? $_GET['endDate'] : null;

    error_log("Parameters received - Type: $type, Start Date: " . ($startDate ?? 'null') . ", End Date: " . ($endDate ?? 'null'));

    // Validate date format if dates are provided
    if ($startDate && !strtotime($startDate)) {
        throw new Exception("Invalid start date format");
    }
    if ($endDate && !strtotime($endDate)) {
        throw new Exception("Invalid end date format");
    }

    $response = [];

    // Equipment Report
    if ($type == 'all' || $type == 'equipment') {
        error_log("Fetching equipment data...");
        
        $equipmentQuery = "
            SELECT 
                e.id,
                e.equipment_name,
                e.equipment_model,
                e.company_name,
                e.contract_number,
                e.total_cost as price,
                e.contract_start_date,
                e.contract_end_date,
                e.payment_type,
                e.status as equipment_status,
                m.maintenance_date,
                m.status as maintenance_status
            FROM equipment e
            LEFT JOIN maintenance m ON e.id = m.equipment_id";
        
        $params = [];
        $whereConditions = [];
        
        if ($startDate && $endDate) {
            $whereConditions[] = "(e.contract_start_date BETWEEN :start_date AND :end_date 
                                OR m.maintenance_date BETWEEN :start_date AND :end_date)";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        }
        
        if (!empty($whereConditions)) {
            $equipmentQuery .= " WHERE " . implode(" AND ", $whereConditions);
        }

        error_log("Equipment Query: " . $equipmentQuery);
        error_log("Query Parameters: " . json_encode($params));
        
        $stmt = $conn->prepare($equipmentQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if (!$stmt->execute()) {
            $error = $stmt->errorInfo();
            throw new Exception("Equipment query failed: " . ($error[2] ?? 'Unknown error'));
        }
        
        $equipmentData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Equipment Data Count: " . count($equipmentData));
        
        foreach ($equipmentData as $item) {
            $response[] = [
                'record_type' => 'Equipment',
                'name' => $item['equipment_name'] . ' (' . $item['equipment_model'] . ')',
                'company_name' => $item['company_name'] ?? '-',
                'contract_number' => $item['contract_number'] ?? '-',
                'start_date' => $item['contract_start_date'] ?? '-',
                'end_date' => $item['contract_end_date'] ?? '-',
                'payment_type' => $item['payment_type'] ?? '-',
                'payment_method' => $item['payment_type'] === 'installment' ? 'Monthly' : 'One Time',
                'total_amount' => number_format($item['price'] ?? 0, 3),
                'status' => $item['equipment_status'] ?? 'Pending'
            ];
        }
    }

    // Clinic Records Report
    if ($type == 'all' || $type == 'clinic') {
        error_log("Fetching clinic records data...");
        
        $clinicQuery = "
            SELECT 
                cr.id,
                cr.record_type,
                cr.company_name,
                cr.contract_number,
                cr.contract_start_date,
                cr.contract_end_date,
                cr.total_cost as amount,
                cr.payment_type,
                cr.down_payment,
                cr.monthly_payment,
                cr.status
            FROM clinic_records cr";
        
        $params = [];
        $whereConditions = [];
        
        if ($startDate && $endDate) {
            $whereConditions[] = "(
                (cr.contract_start_date <= :end_date AND cr.contract_end_date >= :start_date) OR
                (cr.contract_start_date BETWEEN :start_date AND :end_date) OR
                (cr.contract_end_date BETWEEN :start_date AND :end_date)
            )";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        }
        
        if (!empty($whereConditions)) {
            $clinicQuery .= " WHERE " . implode(" AND ", $whereConditions);
        }

        error_log("Clinic Query: " . $clinicQuery);
        error_log("Query Parameters: " . json_encode($params));
        
        $stmt = $conn->prepare($clinicQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if (!$stmt->execute()) {
            $error = $stmt->errorInfo();
            throw new Exception("Clinic records query failed: " . ($error[2] ?? 'Unknown error'));
        }
        
        $clinicData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Clinic Data Count: " . count($clinicData));
        
        foreach ($clinicData as $item) {
            $response[] = [
                'record_type' => 'Clinic Record',
                'name' => $item['record_type'] ?? '',
                'company_name' => $item['company_name'] ?? '-',
                'contract_number' => $item['contract_number'] ?? '-',
                'start_date' => $item['contract_start_date'] ?? '-',
                'end_date' => $item['contract_end_date'] ?? '-',
                'payment_type' => $item['payment_type'] ?? '-',
                'payment_method' => $item['payment_type'] === 'installment' ? 
                    'Monthly (' . number_format($item['monthly_payment'], 3) . ' KWD)' : 
                    'One Time',
                'total_amount' => number_format($item['amount'] ?? 0, 3),
                'status' => $item['status'] ?? 'Pending'
            ];
        }
    }

    // Payments Report
    if ($type == 'all' || $type == 'payments') {
        error_log("Fetching payments data...");
        
        $paymentsQuery = "
            SELECT 
                p.id,
                p.record_type,
                p.record_id,
                p.amount,
                p.payment_date,
                p.payment_type,
                p.reference_no,
                p.status,
                CASE 
                    WHEN p.record_type = 'clinic_record' THEN cr.company_name
                    WHEN p.record_type = 'equipment' THEN e.company_name
                    ELSE '-'
                END as company_name
            FROM payments p
            LEFT JOIN clinic_records cr ON p.record_type = 'clinic_record' AND p.record_id = cr.id
            LEFT JOIN equipment e ON p.record_type = 'equipment' AND p.record_id = e.id";
        
        $params = [];
        $whereConditions = [];
        
        if ($startDate && $endDate) {
            $whereConditions[] = "p.payment_date BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        }
        
        if (!empty($whereConditions)) {
            $paymentsQuery .= " WHERE " . implode(" AND ", $whereConditions);
        }

        error_log("Payments Query: " . $paymentsQuery);
        error_log("Query Parameters: " . json_encode($params));
        
        $stmt = $conn->prepare($paymentsQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if (!$stmt->execute()) {
            $error = $stmt->errorInfo();
            throw new Exception("Payments query failed: " . ($error[2] ?? 'Unknown error'));
        }
        
        $paymentsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Payments Data Count: " . count($paymentsData));
        
        foreach ($paymentsData as $item) {
            $response[] = [
                'record_type' => 'Payment',
                'name' => 'Payment #' . ($item['reference_no'] ?? '-'),
                'company_name' => $item['company_name'] ?? '-',
                'contract_number' => $item['reference_no'] ?? '-',
                'start_date' => $item['payment_date'] ?? '-',
                'end_date' => '-',
                'payment_type' => $item['payment_type'] ?? '-',
                'payment_method' => $item['payment_type'] === 'installment' ? 'Monthly' : 'One Time',
                'total_amount' => number_format($item['amount'] ?? 0, 3),
                'status' => $item['status'] ?? 'Pending'
            ];
        }
    }

    error_log("Total Response Items: " . count($response));
    echo json_encode(['status' => 'success', 'data' => $response]);

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    error_log("Error Code: " . $e->getCode());
    error_log("Error Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    error_log("Error Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
