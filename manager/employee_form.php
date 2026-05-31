<?php
// manager/employee_form.php
// Add or edit an employee that belongs to the logged-in manager.
require_once __DIR__ . '/../includes/auth.php';
require_role('manager');

$pdo       = db();
$managerId = (int)$_SESSION['user_id'];
$editId    = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit    = ($editId !== null);
$pageTitle = $isEdit ? 'Edit Employee' : 'Add Employee';

$errors = array();
$data   = array(
    'name' => '', 'email' => '', 'password' => '',
    'position' => '', 'department' => '', 'salary' => '',
    'phone' => '', 'address' => '', 'hire_date' => '',
);

// --- Load existing data if editing ---
if ($isEdit) {
    // Security: only load employees that belong to THIS manager
    $stmt = $pdo->prepare(
        "SELECT u.id, u.name, u.email,
                e.position, e.department, e.salary, e.phone, e.address, e.hire_date
           FROM users u
           JOIN employees e ON e.user_id = u.id
          WHERE u.id = :id AND e.manager_id = :mid AND u.role = 'employee'"
    );
    $stmt->execute(array(':id' => $editId, ':mid' => $managerId));
    $existing = $stmt->fetch();

    if (!$existing) {
        flash('danger', 'Employee not in your team.');
        redirect(BASE_URL . '/manager/employees.php');
    }

    $data = array_merge($data, $existing);
}

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read and clean all form values
    $data['name']       = trim(isset($_POST['name'])       ? $_POST['name']       : '');
    $data['email']      = trim(isset($_POST['email'])      ? $_POST['email']      : '');
    $data['password']   = trim(isset($_POST['password'])   ? $_POST['password']   : '');
    $data['position']   = trim(isset($_POST['position'])   ? $_POST['position']   : '');
    $data['department'] = trim(isset($_POST['department']) ? $_POST['department'] : '');
    $data['salary']     = trim(isset($_POST['salary'])     ? $_POST['salary']     : '');
    $data['phone']      = trim(isset($_POST['phone'])      ? $_POST['phone']      : '');
    $data['address']    = trim(isset($_POST['address'])    ? $_POST['address']    : '');
    $data['hire_date']  = trim(isset($_POST['hire_date'])  ? $_POST['hire_date']  : '');

    // --- Validation ---
    if ($data['name'] === '')   $errors[] = 'Name is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (!$isEdit && $data['password'] === '') $errors[] = 'Password required.';
    if ($data['password'] !== '' && strlen($data['password']) < 6) $errors[] = 'Password must be at least 6 chars.';
    if ($data['position'] === '') $errors[] = 'Position required.';
    if (!is_numeric($data['salary']) || $data['salary'] < 0) $errors[] = 'Valid salary required.';

    // Check email uniqueness
    if (empty($errors)) {
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
        $chk->execute(array(':email' => $data['email'], ':id' => ($editId ? $editId : 0)));
        if ($chk->fetch()) {
            $errors[] = 'Email already in use.';
        }
    }

    // --- Save to Database ---
    if (empty($errors)) {
        if ($isEdit) {
            // Update user row
            $sql = 'UPDATE users SET name=:name, email=:email, updated_at=NOW()';
            $params = array(':name' => $data['name'], ':email' => $data['email'], ':id' => $editId);
            if ($data['password'] !== '') {
                $sql .= ', password=:pw';
                $params[':pw'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }
            $sql .= ' WHERE id=:id';
            $pdo->prepare($sql)->execute($params);

            // Update employee profile row
            $pdo->prepare(
                "UPDATE employees
                    SET position=:pos, department=:dept, salary=:sal,
                        phone=:phone, address=:addr, hire_date=:hd
                  WHERE user_id=:uid"
            )->execute(array(
                ':pos'   => $data['position'],
                ':dept'  => $data['department'],
                ':sal'   => $data['salary'],
                ':phone' => $data['phone'],
                ':addr'  => $data['address'],
                ':hd'    => $data['hire_date'] !== '' ? $data['hire_date'] : null,
                ':uid'   => $editId,
            ));

            log_activity('UPDATE_EMPLOYEE', 'Manager updated: ' . $data['name'] . ' (ID:' . $editId . ')');
            flash('success', 'Employee "' . $data['name'] . '" updated.');

        } else {
            // Insert new user
            $pw = password_hash($data['password'], PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:n, :e, :p, 'employee')")
                ->execute(array(':n' => $data['name'], ':e' => $data['email'], ':p' => $pw));
            $newId = (int)$pdo->lastInsertId();

            // Insert employee profile (assigned to this manager)
            $pdo->prepare(
                "INSERT INTO employees (user_id, manager_id, position, department, salary, phone, address, hire_date)
                 VALUES (:uid, :mid, :pos, :dept, :sal, :phone, :addr, :hd)"
            )->execute(array(
                ':uid'   => $newId,
                ':mid'   => $managerId,
                ':pos'   => $data['position'],
                ':dept'  => $data['department'],
                ':sal'   => $data['salary'],
                ':phone' => $data['phone'],
                ':addr'  => $data['address'],
                ':hd'    => $data['hire_date'] !== '' ? $data['hire_date'] : null,
            ));

            log_activity('ADD_EMPLOYEE', 'Manager added: ' . $data['name'] . ' (ID:' . $newId . ')');
            flash('success', 'Employee "' . $data['name'] . '" added to your team.');
        }

        redirect(BASE_URL . '/manager/employees.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="app-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>
        <main class="content-area">
            <?php render_flash(); ?>
            <div class="row justify-content-center">
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-person-plus me-2 text-primary"></i>
                            <strong><?php echo $isEdit ? 'Edit Team Member' : 'Add Team Member'; ?></strong>
                        </div>
                        <div class="card-body">

                            <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $err): ?>
                                        <li><?php echo e($err); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="row g-3">
                                    <div class="col-12"><h6 class="form-section-title">Personal</h6></div>
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" name="name" class="form-control" value="<?php echo e($data['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email *</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo e($data['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Password <?php echo $isEdit ? '(blank = keep)' : '*'; ?></label>
                                        <input type="password" name="password" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo e($data['phone']); ?>">
                                    </div>
                                    <div class="col-12"><h6 class="form-section-title mt-2">Job</h6></div>
                                    <div class="col-md-6">
                                        <label class="form-label">Position *</label>
                                        <input type="text" name="position" class="form-control" value="<?php echo e($data['position']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Department</label>
                                        <input type="text" name="department" class="form-control" value="<?php echo e($data['department']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Salary *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="salary" class="form-control" min="0" step="0.01" value="<?php echo e($data['salary']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Hire Date</label>
                                        <input type="date" name="hire_date" class="form-control" value="<?php echo e($data['hire_date']); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" class="form-control" rows="2"><?php echo e($data['address']); ?></textarea>
                                    </div>
                                    <div class="col-12 d-flex gap-2 mt-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-1"></i>
                                            <?php echo $isEdit ? 'Save Changes' : 'Add Employee'; ?>
                                        </button>
                                        <a href="<?php echo BASE_URL; ?>/manager/employees.php" class="btn btn-outline-secondary">Cancel</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
