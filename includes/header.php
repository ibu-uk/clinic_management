<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Clinic Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo $base_url; ?>/assets/css/style.css" rel="stylesheet">
    
    <!-- Core Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Common Functions -->
    <script src="<?php echo $base_url; ?>/assets/js/main.js"></script>
    <!-- Notification Styles -->
    <style>
        #notificationList {
            width: 350px;
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-item {
            border-bottom: 1px solid #eee;
            padding: 10px 15px;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <?php
    // Include notifications handler
    require_once __DIR__ . '/notifications.php';
    
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
    } catch (Exception $e) {
        error_log("Error initializing notifications: " . $e->getMessage());
        $notifications = [];
    }
    ?>
    <div class="wrapper">
        <?php 
        if (isset($_SESSION['user_id'])) {
            include 'includes/sidebar.php';
        }
        ?>
        <div class="content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="d-flex align-items-center">
                        <div class="dropdown">
                            <button class="btn btn-link dropdown-toggle text-dark" type="button" id="notificationDropdown" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span class="badge bg-danger rounded-pill" id="notificationBadge"><?php echo count($notifications) > 0 ? count($notifications) : ''; ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="notificationDropdown" 
                                id="notificationList">
                                <?php if (!empty($notifications)): ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <?php
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
                                        ?>
                                        <li class="notification-item">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-<?php echo $badgeClass; ?> me-2">
                                                    <?php echo ucfirst($notification['type']); ?>
                                                </span>
                                                <div class="flex-grow-1">
                                                    <?php if (!empty($notification['title'])): ?>
                                                        <strong><?php echo htmlspecialchars($notification['title']); ?></strong><br>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($notification['message']); ?>
                                                </div>
                                                <button type="button" class="btn-close ms-2" 
                                                        onclick="markNotificationAsRead(<?php echo $notification['id']; ?>)">
                                                </button>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li><div class="dropdown-item text-muted">No new notifications</div></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="ms-3">
                            <span class="fw-medium">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                        </div>
                    </div>
                </div>
            </nav>

            <?php if (isset($page_title)): ?>
            <div class="page-header mb-4">
                <h2 class="fw-bold"><?php echo htmlspecialchars($page_title); ?></h2>
            </div>
            <?php endif; ?>

            <div class="container-fluid px-4">
                <!-- Display notifications at the top of every page -->
                <div id="notifications-container">
                    <?php
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
                            
                            echo "<div class='alert alert-{$badgeClass} alert-dismissible fade show' role='alert'>
                                    <span class='badge bg-{$badgeClass} me-2'>" . ucfirst($notification['type']) . "</span>
                                    " . (!empty($notification['title']) ? "<strong>" . htmlspecialchars($notification['title']) . "</strong><br>" : "") . "
                                    " . htmlspecialchars($notification['message']) . "
                                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'
                                            onclick='markNotificationAsRead({$notification['id']})'></button>
                                  </div>";
                        }
                    }
                    ?>
                </div>
                <div class="row">
                    <div class="col-12">
                        <nav class="navbar navbar-expand-lg navbar-light p-0 mb-4">
                            <div class="container-fluid p-0">
                                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                                    <span class="navbar-toggler-icon"></span>
                                </button>
                                <div class="collapse navbar-collapse" id="navbarNav">
                                    <ul class="navbar-nav">
                                        <li class="nav-item">
                                            <a href="equipment.php" class="nav-link <?php echo $page == 'equipment' ? 'active' : ''; ?>">
                                                <i class="fas fa-tools me-2"></i>Equipment
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="clinic-records.php" class="nav-link <?php echo $page == 'clinic-records' ? 'active' : ''; ?>">
                                                <i class="fas fa-file-medical me-2"></i>Clinic Records
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link <?php echo ($page == 'payments') ? 'active' : ''; ?>" href="payments.php">
                                                <i class="fas fa-money-bill me-2"></i>Payments
                                            </a>
                                        </li>
                                        <li class="nav-item dropdown">
                                            <a class="nav-link dropdown-toggle <?php echo ($page == 'reports') ? 'active' : ''; ?>" 
                                               href="#" id="reportsDropdown" role="button" 
                                               data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-chart-bar me-2"></i>Reports
                                            </a>
                                            <ul class="dropdown-menu shadow-sm" aria-labelledby="reportsDropdown">
                                                <li><a class="dropdown-item" href="reports.php?type=equipment">Equipment Reports</a></li>
                                                <li><a class="dropdown-item" href="reports.php?type=clinic">Clinic Records Reports</a></li>
                                                <li><a class="dropdown-item" href="reports.php?type=payments">Payment Reports</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="reports.php">All Reports</a></li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </nav>
                    </div>
                </div>