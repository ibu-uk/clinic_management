<?php
require_once '../../config/database.php';

// Validate file parameter
if (!isset($_GET['file']) || empty($_GET['file'])) {
    die('File not specified');
}

$filename = basename($_GET['file']); // Get base name to prevent directory traversal

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Verify file exists in database
    $query = "SELECT contract_file FROM equipment WHERE contract_file = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$filename]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        die('File not found in database');
    }
    
    $filepath = __DIR__ . '/' . $filename;
    
    if (!file_exists($filepath)) {
        die('File not found on server');
    }
    
    // Get file extension
    $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    
    // Set appropriate content type
    switch ($extension) {
        case 'pdf':
            header('Content-Type: application/pdf');
            break;
        case 'doc':
            header('Content-Type: application/msword');
            break;
        case 'docx':
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            break;
        default:
            die('Invalid file type');
    }
    
    // Set headers for file download
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Output file
    readfile($filepath);
    exit;
    
} catch (Exception $e) {
    error_log("Error serving contract file: " . $e->getMessage());
    die('Error serving file');
}
?>
