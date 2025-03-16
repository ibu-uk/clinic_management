<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../includes/notifications.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $notification_id = $data['notification_id'] ?? null;

    if (!$notification_id) {
        throw new Exception('Notification ID is required');
    }

    $database = Database::getInstance();
    $db = $database->getConnection();
    $notificationHandler = new NotificationHandler($db);

    if ($notificationHandler->markAsRead($notification_id)) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to mark notification as read');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
