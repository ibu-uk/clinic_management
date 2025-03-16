<div class="sidebar">
    <div class="sidebar-header">
        <h3 class="text-white mb-0">Clinic Management</h3>
    </div>
    <ul class="nav flex-column mt-3">
        <li class="nav-item">
            <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/index.php">
                <i class="fas fa-home me-2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $page === 'equipment' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/equipment.php">
                <i class="fas fa-tools me-2"></i>
                <span>Equipment</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $page === 'clinic-records' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/clinic-records.php">
                <i class="fas fa-file-medical me-2"></i>
                <span>Clinic Records</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $page === 'payments' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/payments.php">
                <i class="fas fa-money-bill me-2"></i>
                <span>Payments</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $page === 'reports' ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/reports.php">
                <i class="fas fa-chart-bar me-2"></i>
                <span>Reports</span>
            </a>
        </li>
        <li class="nav-item mt-auto">
            <hr class="border-light opacity-25 mx-3">
            <a class="nav-link text-danger" href="<?php echo $base_url; ?>/logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>