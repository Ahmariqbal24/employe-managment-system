<?php
// admin/dashboard.php
// The main admin dashboard showing system-wide statistics.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$pageTitle = 'Admin Dashboard';

// Get a database connection
$pdo = db();

// --- Count all active employees ---
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee' AND is_active = 1");
$totalEmployees = (int)$stmt->fetchColumn();

// --- Count all active managers ---
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'manager' AND is_active = 1");
$totalManagers = (int)$stmt->fetchColumn();

// --- Count all active users (any role) ---
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
$totalUsers = (int)$stmt->fetchColumn();

// --- Count today's activity log entries ---
$stmt = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()");
$totalLogs = (int)$stmt->fetchColumn();

// --- Get employees count per manager ---
$perManager = $pdo->query(
    "SELECT u.name AS manager_name,
            COUNT(e.id) AS emp_count
       FROM users u
  LEFT JOIN employees e ON e.manager_id = u.id
      WHERE u.role = 'manager' AND u.is_active = 1
   GROUP BY u.id, u.name
   ORDER BY emp_count DESC"
)->fetchAll();

// --- Get the 8 most recent activity logs ---
$recentLogs = $pdo->query(
    "SELECT l.action, l.detail, l.ip_address, l.created_at, u.name AS user_name
       FROM activity_logs l
  LEFT JOIN users u ON u.id = l.user_id
   ORDER BY l.created_at DESC
      LIMIT 8"
)->fetchAll();

// --- Get the 5 most recently added employees ---
$latestEmployees = $pdo->query(
    "SELECT u.name, u.email, e.position, e.department, u.created_at
       FROM users u
       JOIN employees e ON e.user_id = u.id
      WHERE u.role = 'employee'
   ORDER BY u.created_at DESC
      LIMIT 5"
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="app-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>

        <main class="content-area">
            <?php render_flash(); ?>

            <!-- Stat Cards Row -->
            <div class="row g-4 mb-4">
                <!-- Total Employees -->
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card stat-card--blue">
                        <div class="stat-card-icon"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-card-info">
                            <div class="stat-card-value"><?php echo $totalEmployees; ?></div>
                            <div class="stat-card-label">Total Employees</div>
                        </div>
                    </div>
                </div>
                <!-- Total Managers -->
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card stat-card--purple">
                        <div class="stat-card-icon"><i class="bi bi-person-badge-fill"></i></div>
                        <div class="stat-card-info">
                            <div class="stat-card-value"><?php echo $totalManagers; ?></div>
                            <div class="stat-card-label">Managers</div>
                        </div>
                    </div>
                </div>
                <!-- Active Users -->
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card stat-card--green">
                        <div class="stat-card-icon"><i class="bi bi-person-check-fill"></i></div>
                        <div class="stat-card-info">
                            <div class="stat-card-value"><?php echo $totalUsers; ?></div>
                            <div class="stat-card-label">Active Users</div>
                        </div>
                    </div>
                </div>
                <!-- Today's Actions -->
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card stat-card--orange">
                        <div class="stat-card-icon"><i class="bi bi-activity"></i></div>
                        <div class="stat-card-info">
                            <div class="stat-card-value"><?php echo $totalLogs; ?></div>
                            <div class="stat-card-label">Actions Today</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Team Distribution (employees per manager) -->
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center">
                            <i class="bi bi-bar-chart-fill me-2 text-primary"></i>
                            <strong>Team Distribution</strong>
                        </div>
                        <div class="card-body p-0">
                            <ul class="manager-list">
                                <?php foreach ($perManager as $m): ?>
                                <li class="manager-list-item">
                                    <div class="manager-avatar">
                                        <?php echo strtoupper(substr($m['manager_name'], 0, 1)); ?>
                                    </div>
                                    <div class="manager-info">
                                        <div class="manager-name"><?php echo e($m['manager_name']); ?></div>
                                        <div class="manager-bar-wrap">
                                            <?php
                                            // Calculate bar width as a percentage (max 100%)
                                            $barWidth = min(100, $m['emp_count'] * 20);
                                            ?>
                                            <div class="manager-bar" style="width:<?php echo $barWidth; ?>%"></div>
                                        </div>
                                    </div>
                                    <span class="manager-count"><?php echo $m['emp_count']; ?></span>
                                </li>
                                <?php endforeach; ?>

                                <?php if (empty($perManager)): ?>
                                <li class="manager-list-item"><em>No managers found.</em></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Recently Added Employees -->
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div><i class="bi bi-clock-history me-2 text-success"></i><strong>Recently Added</strong></div>
                            <a href="<?php echo BASE_URL; ?>/admin/employees.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover ems-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Dept</th>
                                            <th>Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($latestEmployees as $emp): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="table-avatar">
                                                        <?php echo strtoupper(substr($emp['name'], 0, 1)); ?>
                                                    </div>
                                                    <?php echo e($emp['name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo e($emp['position']); ?></td>
                                            <td><?php echo e(isset($emp['department']) ? $emp['department'] : '—'); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($emp['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>

                                        <?php if (empty($latestEmployees)): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-3">No employees yet.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Logs -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div><i class="bi bi-journal-text me-2 text-warning"></i><strong>Recent Activity</strong></div>
                            <a href="<?php echo BASE_URL; ?>/admin/activity_logs.php" class="btn btn-sm btn-outline-warning">Full Log</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover ems-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>Detail</th>
                                            <th>IP</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentLogs as $log): ?>
                                        <tr>
                                            <td><?php echo e(isset($log['user_name']) ? $log['user_name'] : 'System'); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo e($log['action']); ?></span>
                                            </td>
                                            <td class="text-muted small"><?php echo e($log['detail']); ?></td>
                                            <td class="mono small"><?php echo e(isset($log['ip_address']) ? $log['ip_address'] : ''); ?></td>
                                            <td class="small"><?php echo date('M d H:i', strtotime($log['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>

                                        <?php if (empty($recentLogs)): ?>
                                        <tr><td colspan="5" class="text-center text-muted py-3">No activity yet.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- end row -->
        </main>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
