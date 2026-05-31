<?php
// includes/sidebar.php
// Renders the left sidebar navigation.
// The links shown depend on the logged-in user's role.

// Get the current user's info and role
$user = current_user();
$role = isset($user['role']) ? $user['role'] : 'employee';

// Figure out which file and folder we're in (used to highlight the active link)
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));

// Build the dashboard link based on role
if ($role === 'admin') {
    $dashboardUrl = BASE_URL . '/admin/dashboard.php';
} elseif ($role === 'manager') {
    $dashboardUrl = BASE_URL . '/manager/dashboard.php';
} else {
    $dashboardUrl = BASE_URL . '/employee/dashboard.php';
}
?>

<!-- Overlay that darkens the screen when sidebar is open on mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<nav class="sidebar" id="sidebar">
    <!-- App logo and name -->
    <div class="sidebar-brand">
        <span class="sidebar-brand-icon"><i class="bi bi-diagram-3-fill"></i></span>
        <span class="sidebar-brand-text"><?php echo APP_NAME; ?></span>
    </div>

    <!-- Show the logged-in user's avatar and name -->
    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <?php
            // Show the first letter of the user's name as their avatar
            $firstName = isset($user['name']) ? $user['name'] : 'U';
            echo strtoupper(substr($firstName, 0, 1));
            ?>
        </div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo e(isset($user['name']) ? $user['name'] : ''); ?></div>
            <div class="sidebar-user-role badge-role badge-role--<?php echo e($role); ?>">
                <?php echo ucfirst($role); ?>
            </div>
        </div>
    </div>

    <div class="sidebar-divider"></div>

    <!-- Navigation links (different per role) -->
    <ul class="sidebar-nav">

        <?php if ($role === 'admin'): ?>
        <!-- Links for Admin users -->
        <li class="sidebar-nav-item">
            <a href="<?php echo BASE_URL; ?>/admin/dashboard.php"
               class="sidebar-nav-link <?php echo ($currentFile === 'dashboard.php' && $currentDir === 'admin') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="<?php echo BASE_URL; ?>/admin/managers.php"
               class="sidebar-nav-link <?php echo ($currentFile === 'managers.php') ? 'active' : ''; ?>">
                <i class="bi bi-person-badge"></i> Managers
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="<?php echo BASE_URL; ?>/admin/employees.php"
               class="sidebar-nav-link <?php echo ($currentFile === 'employees.php') ? 'active' : ''; ?>">
                <i class="bi bi-people"></i> Employees
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="<?php echo BASE_URL; ?>/admin/activity_logs.php"
               class="sidebar-nav-link <?php echo ($currentFile === 'activity_logs.php') ? 'active' : ''; ?>">
                <i class="bi bi-journal-text"></i> Activity Logs
            </a>
        </li>

        <?php elseif ($role === 'manager'): ?>
        <!-- Links for Manager users -->
        <li class="sidebar-nav-item">
            <a href="<?php echo BASE_URL; ?>/manager/dashboard.php"
               class="sidebar-nav-link <?php echo ($currentFile === 'dashboard.php' && $currentDir === 'manager') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="sidebar-nav-item">
            <a href="<?php echo BASE_URL; ?>/manager/employees.php"
               class="sidebar-nav-link <?php echo ($currentFile === 'employees.php') ? 'active' : ''; ?>">
                <i class="bi bi-people"></i> My Team
            </a>
        </li>

        <?php else: ?>
        <!-- Links for Employee users -->
        <li class="sidebar-nav-item">
            <a href="<?php echo BASE_URL; ?>/employee/dashboard.php"
               class="sidebar-nav-link <?php echo ($currentFile === 'dashboard.php' && $currentDir === 'employee') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>

        <?php endif; ?>

        <!-- Profile link (shown for all roles) -->
        <li class="sidebar-nav-item">
            <a href="<?php echo BASE_URL; ?>/profile.php"
               class="sidebar-nav-link <?php echo ($currentFile === 'profile.php') ? 'active' : ''; ?>">
                <i class="bi bi-person-circle"></i> My Profile
            </a>
        </li>
    </ul>

    <div class="sidebar-divider"></div>

    <!-- Logout link -->
    <ul class="sidebar-nav">
        <li class="sidebar-nav-item">
            <a href="<?php echo BASE_URL; ?>/logout.php" class="sidebar-nav-link sidebar-nav-link--logout">
                <i class="bi bi-box-arrow-left"></i> Logout
            </a>
        </li>
    </ul>

    <!-- App version shown at the bottom of the sidebar -->
    <div class="sidebar-footer">v<?php echo APP_VERSION; ?></div>
</nav>
