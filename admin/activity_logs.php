<?php
// admin/activity_logs.php
// Shows the full paginated activity log (Admin only).
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$pdo       = db();
$pageTitle = 'Activity Logs';

// Get the search keyword from the URL (if any)
$search = trim(isset($_GET['q']) ? $_GET['q'] : '');
$params = array();
$where  = '';

// Build a WHERE clause only if a search keyword was given
if ($search !== '') {
    $where = "WHERE (l.action LIKE :q OR l.detail LIKE :q OR u.name LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}

// Count total matching log entries (for pagination)
$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id " . $where
);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// Calculate pagination
$pg = paginate($total);

// Add LIMIT and OFFSET to params
$params[':lim'] = ITEMS_PER_PAGE;
$params[':off'] = $pg['offset'];

// Fetch the log rows
$stmt = $pdo->prepare(
    "SELECT l.action, l.detail, l.ip_address, l.created_at, u.name AS user_name, u.role AS user_role
       FROM activity_logs l
  LEFT JOIN users u ON u.id = l.user_id
      " . $where . "
   ORDER BY l.created_at DESC
      LIMIT :lim OFFSET :off"
);

// Bind each parameter with the right type
foreach ($params as $k => $v) {
    $type = is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($k, $v, $type);
}
$stmt->execute();
$logs = $stmt->fetchAll();

// Map action names to Bootstrap badge colours for visual clarity
$actionColors = array(
    'LOGIN'           => 'success',
    'LOGOUT'          => 'secondary',
    'ADD_EMPLOYEE'    => 'primary',
    'UPDATE_EMPLOYEE' => 'info',
    'DELETE_EMPLOYEE' => 'danger',
    'ADD_MANAGER'     => 'primary',
    'UPDATE_MANAGER'  => 'info',
    'DELETE_MANAGER'  => 'danger',
    'UPDATE_PROFILE'  => 'warning',
);

include __DIR__ . '/../includes/header.php';
?>

<div class="app-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>
        <main class="content-area">
            <?php render_flash(); ?>

            <!-- Page heading -->
            <div class="d-flex align-items-center gap-3 mb-4">
                <h5 class="mb-0 me-auto">
                    Activity Logs
                    <span class="badge bg-secondary ms-1"><?php echo $total; ?></span>
                </h5>
            </div>

            <!-- Search Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-sm-7 col-md-5">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" name="q" class="form-control"
                                       placeholder="Search action, detail, user…"
                                       value="<?php echo e($search); ?>">
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-secondary">Search</button>
                            <a href="<?php echo BASE_URL; ?>/admin/activity_logs.php" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover ems-table mb-0">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                    <th>Detail</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="small text-nowrap">
                                        <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td><?php echo e(isset($log['user_name']) ? $log['user_name'] : '—'); ?></td>
                                    <td>
                                        <?php if ($log['user_role']): ?>
                                        <span class="badge-role badge-role--<?php echo e($log['user_role']); ?>">
                                            <?php echo ucfirst(e($log['user_role'])); ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Look up the badge color for this action, default to 'secondary'
                                        $color = isset($actionColors[$log['action']]) ? $actionColors[$log['action']] : 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>">
                                            <?php echo e($log['action']); ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?php echo e($log['detail']); ?></td>
                                    <td class="mono small"><?php echo e(isset($log['ip_address']) ? $log['ip_address'] : ''); ?></td>
                                </tr>
                                <?php endforeach; ?>

                                <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-journal-x fs-4 d-block mb-2"></i>
                                        No log entries found.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($pg['total_pages'] > 1): ?>
                <div class="card-footer">
                    <?php
                    // Build base URL preserving the search keyword
                    $base = BASE_URL . '/admin/activity_logs.php';
                    if ($search) {
                        $base .= '?q=' . urlencode($search);
                    }
                    render_pagination($pg['total_pages'], $pg['current_page'], $base);
                    ?>
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
