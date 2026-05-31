<?php
// admin/managers.php
// List, add, edit, and delete managers (Admin only).
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$pdo       = db();
$pageTitle = 'Manage Managers';

// --- Handle Delete Request ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];

    // Look up the manager before deleting
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = :id AND role = 'manager'");
    $stmt->execute(array(':id' => $delId));
    $delUser = $stmt->fetch();

    if ($delUser) {
        // Unassign all employees from this manager first
        $pdo->prepare("UPDATE employees SET manager_id = NULL WHERE manager_id = :mid")
            ->execute(array(':mid' => $delId));
        // Now delete the manager
        $pdo->prepare("DELETE FROM users WHERE id = :id")->execute(array(':id' => $delId));
        log_activity('DELETE_MANAGER', 'Deleted manager: ' . $delUser['name'] . ' (ID:' . $delId . ')');
        flash('success', 'Manager "' . $delUser['name'] . '" deleted.');
    } else {
        flash('danger', 'Manager not found.');
    }

    redirect(BASE_URL . '/admin/managers.php');
}

// --- Count active managers (for pagination) ---
$stmt  = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'manager' AND is_active = 1");
$total = (int)$stmt->fetchColumn();

$pg = paginate($total);

// --- Fetch managers with their employee counts ---
$stmt = $pdo->prepare(
    "SELECT u.id, u.name, u.email, u.created_at,
            e.position, e.department, e.salary, e.phone,
            (SELECT COUNT(*) FROM employees ee WHERE ee.manager_id = u.id) AS emp_count
       FROM users u
  LEFT JOIN employees e ON e.user_id = u.id
      WHERE u.role = 'manager' AND u.is_active = 1
   ORDER BY u.name
      LIMIT :lim OFFSET :off"
);
$stmt->bindValue(':lim', ITEMS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':off', $pg['offset'],  PDO::PARAM_INT);
$stmt->execute();
$managers = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="app-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>

        <main class="content-area">
            <?php render_flash(); ?>

            <!-- Page Header -->
            <div class="d-flex align-items-center gap-3 mb-4">
                <h5 class="mb-0 me-auto">
                    Managers
                    <span class="badge bg-purple ms-1"><?php echo $total; ?></span>
                </h5>
                <a href="<?php echo BASE_URL; ?>/admin/manager_form.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Manager
                </a>
            </div>

            <!-- Manager Cards Grid -->
            <div class="row g-4">
                <?php foreach ($managers as $mgr): ?>
                <div class="col-sm-6 col-xl-4">
                    <div class="card manager-card">
                        <div class="card-body">
                            <!-- Manager name and email -->
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="manager-card-avatar">
                                    <?php echo strtoupper(substr($mgr['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?php echo e($mgr['name']); ?></div>
                                    <div class="small text-muted"><?php echo e($mgr['email']); ?></div>
                                </div>
                            </div>
                            <!-- Manager details (position, dept, team size, salary) -->
                            <div class="manager-card-meta">
                                <span><i class="bi bi-briefcase me-1"></i><?php echo e(isset($mgr['position']) ? $mgr['position'] : '—'); ?></span>
                                <span><i class="bi bi-building me-1"></i><?php echo e(isset($mgr['department']) ? $mgr['department'] : '—'); ?></span>
                                <span><i class="bi bi-people me-1"></i><?php echo $mgr['emp_count']; ?> employees</span>
                                <span><i class="bi bi-currency-dollar me-1"></i><?php echo $mgr['salary'] ? '$' . number_format($mgr['salary'], 0) : '—'; ?></span>
                            </div>
                        </div>
                        <!-- Card action buttons -->
                        <div class="card-footer d-flex gap-2">
                            <a href="<?php echo BASE_URL; ?>/admin/manager_form.php?id=<?php echo $mgr['id']; ?>"
                               class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="bi bi-pencil me-1"></i> Edit
                            </a>
                            <a href="<?php echo BASE_URL; ?>/admin/managers.php?delete=<?php echo $mgr['id']; ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Delete <?php echo e(addslashes($mgr['name'])); ?>?\nTheir employees will be unassigned.')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Empty state if no managers -->
                <?php if (empty($managers)): ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="bi bi-person-badge"></i>
                        <p>No managers yet. <a href="<?php echo BASE_URL; ?>/admin/manager_form.php">Add one</a>.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($pg['total_pages'] > 1): ?>
            <div class="mt-4">
                <?php render_pagination($pg['total_pages'], $pg['current_page'], BASE_URL . '/admin/managers.php'); ?>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
