<?php
require_once 'config/database.php';

if (!class_exists('NotificationHandler')) {
    class NotificationHandler {
        private $db;

        public function __construct($db) {
            $this->db = $db;
        }

        public function createNotification($type, $referenceType, $referenceId, $title, $message, $dueDate = null) {
            try {
                $query = "INSERT INTO notifications (
                    type, reference_type, reference_id, title, message, due_date, status
                ) VALUES (
                    :type, :reference_type, :reference_id, :title, :message, :due_date, 'pending'
                )";

                $stmt = $this->db->prepare($query);
                return $stmt->execute([
                    ':type' => $type,
                    ':reference_type' => $referenceType,
                    ':reference_id' => $referenceId,
                    ':title' => $title,
                    ':message' => $message,
                    ':due_date' => $dueDate
                ]);
            } catch (PDOException $e) {
                error_log("Error creating notification: " . $e->getMessage());
                return false;
            }
        }

        public function getUnreadNotifications() {
            try {
                $query = "SELECT * FROM notifications 
                         WHERE status = 'pending' 
                         ORDER BY created_at DESC 
                         LIMIT 10";
                
                $stmt = $this->db->query($query);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Error getting notifications: " . $e->getMessage());
                return [];
            }
        }

        public function markAsRead($notificationId) {
            try {
                $query = "UPDATE notifications 
                         SET status = 'read', 
                             updated_at = NOW() 
                         WHERE id = :id";
                
                $stmt = $this->db->prepare($query);
                return $stmt->execute([':id' => $notificationId]);
            } catch (PDOException $e) {
                error_log("Error marking notification as read: " . $e->getMessage());
                return false;
            }
        }

        public function checkMaintenanceSchedule() {
            try {
                // Check for upcoming maintenance within next 7 days
                $query = "SELECT m.*, e.equipment_name 
                         FROM maintenance m
                         JOIN equipment e ON m.equipment_id = e.id
                         WHERE m.status = 'scheduled'
                         AND m.maintenance_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                
                $stmt = $this->db->query($query);
                $maintenances = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($maintenances as $maintenance) {
                    $this->createNotification(
                        'maintenance',
                        'equipment',
                        $maintenance['equipment_id'],
                        'Upcoming Maintenance',
                        "Maintenance scheduled for {$maintenance['equipment_name']} on " . date('Y-m-d', strtotime($maintenance['maintenance_date'])),
                        $maintenance['maintenance_date']
                    );
                }
            } catch (PDOException $e) {
                error_log("Error checking maintenance schedule: " . $e->getMessage());
            }
        }

        public function checkPaymentSchedule() {
            try {
                // Check for upcoming payments within next 7 days
                $query = "SELECT mi.*, 
                            CASE 
                                WHEN mi.record_type = 'equipment' THEN e.equipment_name
                                ELSE cr.company_name
                            END as record_name
                         FROM monthly_installments mi
                         LEFT JOIN equipment e ON mi.record_type = 'equipment' AND mi.record_id = e.id
                         LEFT JOIN clinic_records cr ON mi.record_type = 'clinic_record' AND mi.record_id = cr.id
                         WHERE mi.status = 'pending'
                         AND mi.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                
                $stmt = $this->db->query($query);
                $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($payments as $payment) {
                    $this->createNotification(
                        'payment',
                        $payment['record_type'],
                        $payment['record_id'],
                        'Upcoming Payment',
                        "Payment of " . number_format($payment['amount'], 3) . " KWD due for {$payment['record_name']}",
                        $payment['due_date']
                    );
                }
            } catch (PDOException $e) {
                error_log("Error checking payment schedule: " . $e->getMessage());
            }
        }
    }
}

// Initialize notification handler
try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    $notificationHandler = new NotificationHandler($db);

    // Check schedules
    $notificationHandler->checkMaintenanceSchedule();
    $notificationHandler->checkPaymentSchedule();

    // Get unread notifications
    $notifications = $notificationHandler->getUnreadNotifications();

    // Display notifications
    if (!empty($notifications)) {
        foreach ($notifications as $notification) {
            $badgeClass = '';
            switch ($notification['type']) {
                case 'maintenance':
                    $badgeClass = 'warning';
                    break;
                case 'payment':
                    $badgeClass = 'danger';
                    break;
                case 'expiry':
                    $badgeClass = 'info';
                    break;
                default:
                    $badgeClass = 'secondary';
            }
            
            // Format the notification message
            $title = htmlspecialchars($notification['title'] ?? '');
            $message = htmlspecialchars($notification['message'] ?? '');
            $type = ucfirst($notification['type']);
            
            echo "<div class='alert alert-{$badgeClass} alert-dismissible fade show' role='alert'>
                    <span class='badge bg-{$badgeClass} me-2'>{$type}</span>
                    " . ($title ? "<strong>{$title}</strong><br>" : "") . "
                    {$message}
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'
                            onclick='markNotificationAsRead({$notification['id']})'></button>
                  </div>";
        }
    }
} catch (Exception $e) {
    error_log("Error in notifications: " . $e->getMessage());
    // Fail silently in production
}
?>

<script>
function markNotificationAsRead(id) {
    fetch('api/notifications/mark_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: id })
    });
}
</script>
