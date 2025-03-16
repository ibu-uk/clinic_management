<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path if not already defined
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(dirname(__FILE__)));
}

// Set the base URL for the application
$base_url = '/clinic_management';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once BASEPATH . '/config/database.php';

// Initialize database connection
try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Failed to connect to database. Please check your configuration.");
    }
} catch (Exception $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
