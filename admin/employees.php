<?php
// admin/employees.php
// Lists all employees with search, filter, pagination, and delete.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$pageTitle = 'Manage Employees';
$pdo = db();

// --- Handle Delete Request ---
// Check if a delete ID was passed in the URL (e.g. ?delete=5)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];

    // Fetch the employee info before deleting (for the log message)
    $stmt = $pdo->prepare("SELECT name, role FROM users WHERE id = :id AND role = 'employee'");
    $stmt->execute(array(':id' => $delId));
    $delUser = $stmt->fetch();

    if ($delUser) {
        // Delete the user
        $pdo->prepare("DELETE FROM users WHERE id = :id")->execute(array(':id' => $delId));
        log_activity('DELETE_EMPLOYEE', 'Deleted employee: ' . $delUser['name'] . ' (ID:' . $delId . ')');
        flash('success', 'Employee "' . $delUser['name'] . '" deleted.');
    } else {
        flash('danger', 'Employee not found.');
    }

    redirect(BASE_URL . '/admin/employees.php');
}

// --- Search and Filter ---
$search     = trim(isset($_GET['q'])       ? $_GET['q']       : '');
$mgr_filter = (int)(isset($_GET['manager']) ? $_GET['manager'] : 0);

// Build the WHERE clause dynamically based on what filters are active
$whereParts = array("u.role = 'employee'", "u.is_active = 1");
$params     = array();

// Add search filter if a keyword was entered
if ($search !== '') {
    $whereParts[] = "(u.name LIKE :q OR e.position LIKE :q OR e.department LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}

// Add manager filter if a manager was selected
if ($mgr_filter > 0) {
    $whereParts[] = "e.manager_id = :mid";
    $params[':mid'] = $mgr_filter;
}

// Join all the WHERE conditions with AND
$where = 'WHERE ' . implode(' AND ', $whereParts);

// --- Count total matching rows (for pagination) ---
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u JOIN employees e ON e.user_id = u.id " . $where);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// Calculate pagination
$pg = paginate($total);

// Add LIMIT and OFFSET for the main query
$params[':limit']  = ITEMS_PER_PAGE;
$params[':offset'] = $pg['offset'];

// --- Fetch Employees ---
$sql = "SELECT u.id, u.name, u.email, u.created_at,
               e.position, e.department, e.salary, e.phone, e.hire_date,
               m.name AS manager_name
          FROM users u
          JOIN employees e ON e.user_id = u.id
     LEFT JOIN users m ON m.id = e.manager_id
        " . $where . "
        ORDER BY u.created_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

// Bind each parameter with the correct type
foreach ($params as $key => $val) {
    $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $val, $type);
}
$stmt->execute();
$employees = $stmt->fetchAll();

// --- Get Manager List for Filter Dropdown ---
$managers = $pdo->query(
    "SELECT id, name FROM users WHERE role = 'manager' AND is_active = 1 ORDER BY name"
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="app-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>

        <main class="content-area">
            <?php render_flash(); ?>

            <!-- Toolbar: heading + Add button -->
            <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                <h5 class="mb-0 me-auto">
                    All Employees
                    <span class="badge bg-primary ms-1"><?php echo $total; ?></span>
                </h5>
                <a href="<?php echo BASE_URL; ?>/admin/employee_form.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Employee
                </a>
            </div>

            <!-- Search and Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <!-- Search input -->
                        <div class="col-sm-6 col-md-5">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" name="q" class="form-control"
                                       placeholder="Search name, position, dept…"
                                       value="<?php echo e($search); ?>">
                            </div>
                        </div>
                        <!-- Manager filter dropdown -->
                        <div class="col-sm-4 col-md-3">
                            <select name="manager" class="form-select">
                                <option value="">— All Managers —</option>
                                <?php foreach ($managers as $m): ?>
                                <option value="<?php echo $m['id']; ?>"
                                        <?php echo ($mgr_filter === (int)$m['id']) ? 'selected' : ''; ?>>
                                    <?php echo e($m['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Filter and Clear buttons -->
                        <div class="col-auto">
                            <button type="submit" class="btn btn-secondary">Filter</button>
                            <a href="<?php echo BASE_URL; ?>/admin/employees.php" class="btn btn-outline-secondary">Clear</a>
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
                                    <th>Manager</th>
                                    <th>Salary</th>
                                    <th>Joined</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $i => $emp): ?>
                                <tr>
                                    <td class="text-muted small"><?php echo $pg['offset'] + $i + 1; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="table-avatar">
                                                <?php echo strtoupper(substr($emp['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div><?php echo e($emp['name']); ?></div>
                                                <div class="small text-muted"><?php echo e($emp['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo e($emp['position']); ?></td>
                                    <td><?php echo e(isset($emp['department']) ? $emp['department'] : '—'); ?></td>
                                    <td><?php echo e(isset($emp['manager_name']) ? $emp['manager_name'] : 'Unassigned'); ?></td>
                                    <td class="mono">$<?php echo number_format($emp['salary'], 2); ?></td>
                                    <td><?php echo $emp['hire_date'] ? date('M d, Y', strtotime($emp['hire_date'])) : '—'; ?></td>
                                    <td class="text-center">
                                        <!-- Edit button -->
                                        <a href="<?php echo BASE_URL; ?>/admin/employee_form.php?id=<?php echo $emp['id']; ?>"
                                           class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <!-- Delete button with confirmation -->
                                        <a href="<?php echo BASE_URL; ?>/admin/employees.php?delete=<?php echo $emp['id']; ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           title="Delete"
                                           onclick="return confirm('Delete <?php echo e(addslashes($emp['name'])); ?>?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <?php if (empty($employees)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                        No employees found.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination (only shown if more than 1 page) -->
                <?php if ($pg['total_pages'] > 1): ?>
                <div class="card-footer">
                    <?php
                    // Build the base URL preserving search/filter params
                    $baseUrl = BASE_URL . '/admin/employees.php?';
                    if ($search)     $baseUrl .= 'q='       . urlencode($search)  . '&';
                    if ($mgr_filter) $baseUrl .= 'manager=' . $mgr_filter         . '&';
                    $baseUrl = rtrim($baseUrl, '&?');
                    render_pagination($pg['total_pages'], $pg['current_page'], $baseUrl);
                    ?>
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
