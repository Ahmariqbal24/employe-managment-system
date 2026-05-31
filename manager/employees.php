<?php
// manager/employees.php
// Manager sees ONLY their own team. Can add, edit, and delete those employees.
require_once __DIR__ . '/../includes/auth.php';
require_role('manager');

$pdo       = db();
$managerId = (int)$_SESSION['user_id'];
$pageTitle = 'My Team';

// --- Handle Delete ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];

    // Security check: make sure this employee actually belongs to this manager
    $chk = $pdo->prepare(
        "SELECT u.name FROM users u
           JOIN employees e ON e.user_id = u.id
          WHERE u.id = :id AND e.manager_id = :mid AND u.role = 'employee'"
    );
    $chk->execute(array(':id' => $delId, ':mid' => $managerId));
    $delUser = $chk->fetch();

    if ($delUser) {
        $pdo->prepare("DELETE FROM users WHERE id = :id")->execute(array(':id' => $delId));
        log_activity('DELETE_EMPLOYEE', 'Manager deleted employee: ' . $delUser['name'] . ' (ID:' . $delId . ')');
        flash('success', 'Employee "' . $delUser['name'] . '" removed from your team.');
    } else {
        flash('danger', 'Employee not found or not in your team.');
    }

    redirect(BASE_URL . '/manager/employees.php');
}

// --- Search ---
$search = trim(isset($_GET['q']) ? $_GET['q'] : '');
$params = array(':mid' => $managerId);
$extraWhere = '';

// Add keyword filter if search is active
if ($search !== '') {
    $extraWhere = "AND (u.name LIKE :q OR e.position LIKE :q OR e.department LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}

// Count matching rows for pagination
$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM users u JOIN employees e ON e.user_id = u.id
      WHERE e.manager_id = :mid AND u.role = 'employee' AND u.is_active = 1 " . $extraWhere
);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pg = paginate($total);
$params[':lim'] = ITEMS_PER_PAGE;
$params[':off'] = $pg['offset'];

// Fetch matching employees
$stmt = $pdo->prepare(
    "SELECT u.id, u.name, u.email, e.position, e.department, e.salary, e.phone, e.hire_date
       FROM users u
       JOIN employees e ON e.user_id = u.id
      WHERE e.manager_id = :mid AND u.role = 'employee' AND u.is_active = 1 " . $extraWhere . "
   ORDER BY u.name
      LIMIT :lim OFFSET :off"
);

foreach ($params as $k => $v) {
    $type = is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($k, $v, $type);
}
$stmt->execute();
$employees = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="app-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>
        <main class="content-area">
            <?php render_flash(); ?>

            <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                <h5 class="mb-0 me-auto">
                    My Team
                    <span class="badge bg-primary ms-1"><?php echo $total; ?></span>
                </h5>
                <a href="<?php echo BASE_URL; ?>/manager/employee_form.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Employee
                </a>
            </div>

            <!-- Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-sm-6 col-md-5">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" name="q" class="form-control"
                                       placeholder="Name, position, dept…"
                                       value="<?php echo e($search); ?>">
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-secondary">Search</button>
                            <a href="<?php echo BASE_URL; ?>/manager/employees.php" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Employees Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover ems-table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Dept</th>
                                    <th>Salary</th>
                                    <th>Hire Date</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $i => $emp): ?>
                                <tr>
                                    <td class="text-muted small"><?php echo $pg['offset'] + $i + 1; ?></td>
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
                                    <td><?php echo $emp['hire_date'] ? date('M d, Y', strtotime($emp['hire_date'])) : '—'; ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo BASE_URL; ?>/manager/employee_form.php?id=<?php echo $emp['id']; ?>"
                                           class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/manager/employees.php?delete=<?php echo $emp['id']; ?>"
                                           class="btn btn-sm btn-outline-danger" title="Remove"
                                           onclick="return confirm('Remove <?php echo e(addslashes($emp['name'])); ?> from your team?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <?php if (empty($employees)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                        Your team is empty.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($pg['total_pages'] > 1): ?>
                <div class="card-footer">
                    <?php
                    $base = BASE_URL . '/manager/employees.php';
                    if ($search) $base .= '?q=' . urlencode($search);
                    render_pagination($pg['total_pages'], $pg['current_page'], $base);
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
