<?php
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(dirname(__FILE__)));
}

require_once BASEPATH . '/config/database.php';

function getPDO() {
    return Database::getInstance()->getConnection();
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function formatMoney($amount) {
    return number_format($amount, 2, '.', ',');
}

function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length));
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function redirectTo($path) {
    header("Location: $path");
    exit();
}

function getCurrentDateTime() {
    return (new DateTime())->format('Y-m-d H:i:s');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirectTo('/login.php');
    }
}

function logError($message, $context = []) {
    $logFile = BASEPATH . '/logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[$timestamp] $message $contextStr\n";
    error_log($logMessage, 3, $logFile);
}
