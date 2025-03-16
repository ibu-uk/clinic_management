<?php
session_start();
header('Content-Type: application/json');

// For demonstration, using hardcoded credentials
// In production, use proper password hashing and database storage
$valid_username = "admin";
$valid_password = "admin123";

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($username === $valid_username && $password === $valid_password) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = $username;
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid username or password'
    ]);
}
?>
