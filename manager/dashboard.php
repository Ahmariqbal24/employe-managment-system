<?php
// manager/dashboard.php
// Dashboard for Manager role – shows stats for their own team.
require_once __DIR__ . '/../includes/auth.php';
require_role('manager');

$pageTitle = 'Manager Dashboard';
$pdo       = db();
$managerId = (int)$_SESSION['user_id']; // the ID of the logged-in manager

// --- Count how many employees are on this manager's team ---
$stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE manager_id = :mid");
$stmt->execute(array(':mid' => $managerId));
$totalTeam = (int)$stmt->fetchColumn();

// --- Get payroll totals for this manager's team ---
$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(e.salary), 0) AS total_payroll,
            COALESCE(AVG(e.salary), 0) AS avg_salary
       FROM employees e
      WHERE e.manager_id = :mid"
);
$stmt->execute(array(':mid' => $managerId));
$payrollRow = $stmt->fetch();

// --- Count employees per department ---
$stmt = $pdo->prepare(
    "SELECT e.department, COUNT(*) AS cnt
       FROM employees e
       JOIN users u ON u.id = e.user_id
      WHERE e.manager_id = :mid AND u.is_active = 1
      GROUP BY e.department
      ORDER BY cnt DESC"
);
$stmt->execute(array(':mid' => $managerId));
$depts = $stmt->fetchAll();

// --- Get 5 most recently added team members ---
$stmt = $pdo->prepare(
    "SELECT u.name, u.email, u.created_at, e.position, e.department, e.salary
       FROM users u
       JOIN employees e ON e.user_id = u.id
      WHERE e.manager_id = :mid AND u.is_active = 1
      ORDER BY u.created_at DESC
      LIMIT 5"
);
$stmt->execute(array(':mid' => $managerId));
$recentTeam = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="app-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>
        <main class="content-area">
            <?php render_flash(); ?>

            <!-- Stat Cards -->
            <div class="row g-4 mb-4">
                <!-- Team size -->
                <div class="col-sm-6 col-xl-4">
                    <div class="stat-card stat-card--blue">
                        <div class="stat-card-icon"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-card-info">
                            <div class="stat-card-value"><?php echo $totalTeam; ?></div>
                            <div class="stat-card-label">My Team</div>
                        </div>
                    </div>
                </div>
                <!-- Total payroll -->
                <div class="col-sm-6 col-xl-4">
                    <div class="stat-card stat-card--green">
                        <div class="stat-card-icon"><i class="bi bi-cash-stack"></i></div>
                        <div class="stat-card-info">
                            <div class="stat-card-value">$<?php echo number_format($payrollRow['total_payroll'], 0); ?></div>
                            <div class="stat-card-label">Team Payroll</div>
                        </div>
                    </div>
                </div>
                <!-- Average salary -->
                <div class="col-sm-6 col-xl-4">
                    <div class="stat-card stat-card--orange">
                        <div class="stat-card-icon"><i class="bi bi-graph-up"></i></div>
                        <div class="stat-card-info">
                            <div class="stat-card-value">$<?php echo number_format($payrollRow['avg_salary'], 0); ?></div>
                            <div class="stat-card-label">Avg Salary</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Departments Breakdown -->
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="bi bi-building me-2 text-info"></i>
                            <strong>Departments</strong>
                        </div>
                        <div class="card-body p-0">
                            <ul class="manager-list">
                                <?php foreach ($depts as $d): ?>
                                <li class="manager-list-item">
                                    <div class="manager-info">
                                        <div class="manager-name"><?php echo e($d['department'] ? $d['department'] : 'Unassigned'); ?></div>
                                        <div class="manager-bar-wrap">
                                            <?php
                                            // Bar width as a percentage of total team size
                                            $barWidth = $totalTeam > 0 ? round($d['cnt'] / $totalTeam * 100) : 0;
                                            ?>
                                            <div class="manager-bar" style="width:<?php echo $barWidth; ?>%"></div>
                                        </div>
                                    </div>
                                    <span class="manager-count"><?php echo $d['cnt']; ?></span>
                                </li>
                                <?php endforeach; ?>

                                <?php if (empty($depts)): ?>
                                <li class="manager-list-item text-muted">No departments yet.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Recent Team Members -->
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div><i class="bi bi-clock-history me-2 text-success"></i><strong>Recent Team Members</strong></div>
                            <a href="<?php echo BASE_URL; ?>/manager/employees.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover ems-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Dept</th>
                                            <th>Salary</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentTeam as $emp): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="table-avatar"><?php echo strtoupper(substr($emp['name'], 0, 1)); ?></div>
                                                    <div>
                                                        <div><?php echo e($emp['name']); ?></div>
                                                        <div class="small text-muted"><?php echo e($emp['email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo e($emp['position']); ?></td>
                                            <td><?php echo e(isset($emp['department']) ? $emp['department'] : '—'); ?></td>
                                            <td class="mono">$<?php echo number_format($emp['salary'], 0); ?></td>
                                        </tr>
                                        <?php endforeach; ?>

                                        <?php if (empty($recentTeam)): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-3">No team members yet.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
