<?php
// admin/employee_form.php
// Add a new employee or edit an existing one (Admin only).
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$pdo = db();

// Check if an ID was passed in the URL (edit mode) or not (add mode)
$editId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = ($editId !== null); // true if editing, false if adding

$pageTitle = $isEdit ? 'Edit Employee' : 'Add Employee';

// Collect any validation errors here
$errors = array();

// Default empty form values
$data = array(
    'name'       => '',
    'email'      => '',
    'password'   => '',
    'position'   => '',
    'department' => '',
    'salary'     => '',
    'phone'      => '',
    'address'    => '',
    'hire_date'  => '',
    'manager_id' => '',
);

// --- If editing, load the existing employee's data ---
if ($isEdit) {
    $stmt = $pdo->prepare(
        "SELECT u.id, u.name, u.email, u.role,
                e.position, e.department, e.salary, e.phone, e.address, e.hire_date, e.manager_id
           FROM users u
           JOIN employees e ON e.user_id = u.id
          WHERE u.id = :id AND u.role = 'employee'"
    );
    $stmt->execute(array(':id' => $editId));
    $existing = $stmt->fetch();

    // If the employee wasn't found, redirect with an error
    if (!$existing) {
        flash('danger', 'Employee not found.');
        redirect(BASE_URL . '/admin/employees.php');
    }

    // Merge existing values into $data (overwrite the empty defaults)
    $data = array_merge($data, $existing);
}

// --- Get manager list for the dropdown ---
$managers = $pdo->query(
    "SELECT id, name FROM users WHERE role = 'manager' AND is_active = 1 ORDER BY name"
)->fetchAll();

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read and clean all form fields
    $data['name']       = trim(isset($_POST['name'])       ? $_POST['name']       : '');
    $data['email']      = trim(isset($_POST['email'])      ? $_POST['email']      : '');
    $data['password']   = trim(isset($_POST['password'])   ? $_POST['password']   : '');
    $data['position']   = trim(isset($_POST['position'])   ? $_POST['position']   : '');
    $data['department'] = trim(isset($_POST['department']) ? $_POST['department'] : '');
    $data['salary']     = trim(isset($_POST['salary'])     ? $_POST['salary']     : '');
    $data['phone']      = trim(isset($_POST['phone'])      ? $_POST['phone']      : '');
    $data['address']    = trim(isset($_POST['address'])    ? $_POST['address']    : '');
    $data['hire_date']  = trim(isset($_POST['hire_date'])  ? $_POST['hire_date']  : '');

    // Handle manager_id: if empty string, set to null; otherwise cast to int
    if (isset($_POST['manager_id']) && $_POST['manager_id'] !== '') {
        $data['manager_id'] = (int)$_POST['manager_id'];
    } else {
        $data['manager_id'] = null;
    }

    // --- Validation ---
    if ($data['name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email required.';
    }
    // Password is only required when adding a new employee
    if (!$isEdit && $data['password'] === '') {
        $errors[] = 'Password is required for new employees.';
    }
    if ($data['password'] !== '' && strlen($data['password']) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($data['position'] === '') {
        $errors[] = 'Position is required.';
    }
    if (!is_numeric($data['salary']) || $data['salary'] < 0) {
        $errors[] = 'Valid salary required.';
    }

    // Check if another user already has the same email
    if (empty($errors)) {
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
        $chk->execute(array(':email' => $data['email'], ':id' => ($editId ? $editId : 0)));
        if ($chk->fetch()) {
            $errors[] = 'That email is already in use.';
        }
    }

    // --- Save to Database if no errors ---
    if (empty($errors)) {
        if ($isEdit) {
            // Update the user's basic info
            $userSql = 'UPDATE users SET name=:name, email=:email, updated_at=NOW()';
            $userParams = array(
                ':name'  => $data['name'],
                ':email' => $data['email'],
                ':id'    => $editId,
            );

            // Only update password if a new one was entered
            if ($data['password'] !== '') {
                $userSql .= ', password=:password';
                $userParams[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }

            $userSql .= ' WHERE id=:id';
            $pdo->prepare($userSql)->execute($userParams);

            // Update the employee profile row
            $pdo->prepare(
                'UPDATE employees
                    SET manager_id=:mid, position=:pos, department=:dept,
                        salary=:sal, phone=:phone, address=:addr, hire_date=:hd
                  WHERE user_id=:uid'
            )->execute(array(
                ':mid'   => $data['manager_id'],
                ':pos'   => $data['position'],
                ':dept'  => $data['department'],
                ':sal'   => $data['salary'],
                ':phone' => $data['phone'],
                ':addr'  => $data['address'],
                ':hd'    => $data['hire_date'] !== '' ? $data['hire_date'] : null,
                ':uid'   => $editId,
            ));

            log_activity('UPDATE_EMPLOYEE', 'Updated employee: ' . $data['name'] . ' (ID:' . $editId . ')');
            flash('success', 'Employee "' . $data['name'] . '" updated.');

        } else {
            // Insert a new user row
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
            $pdo->prepare(
                "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :pw, 'employee')"
            )->execute(array(
                ':name'  => $data['name'],
                ':email' => $data['email'],
                ':pw'    => $hashedPassword,
            ));

            // Get the new user's ID
            $newId = (int)$pdo->lastInsertId();

            // Insert the employee profile row
            $pdo->prepare(
                'INSERT INTO employees (user_id, manager_id, position, department, salary, phone, address, hire_date)
                 VALUES (:uid, :mid, :pos, :dept, :sal, :phone, :addr, :hd)'
            )->execute(array(
                ':uid'   => $newId,
                ':mid'   => $data['manager_id'],
                ':pos'   => $data['position'],
                ':dept'  => $data['department'],
                ':sal'   => $data['salary'],
                ':phone' => $data['phone'],
                ':addr'  => $data['address'],
                ':hd'    => $data['hire_date'] !== '' ? $data['hire_date'] : null,
            ));

            log_activity('ADD_EMPLOYEE', 'Added employee: ' . $data['name'] . ' (ID:' . $newId . ')');
            flash('success', 'Employee "' . $data['name'] . '" added successfully.');
        }

        redirect(BASE_URL . '/admin/employees.php');
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
                            <strong><?php echo $isEdit ? 'Edit Employee' : 'Add New Employee'; ?></strong>
                        </div>
                        <div class="card-body">

                            <!-- Show validation errors -->
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
                                    <!-- Personal Information Section -->
                                    <div class="col-12">
                                        <h6 class="form-section-title">Personal Information</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control"
                                               value="<?php echo e($data['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control"
                                               value="<?php echo e($data['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Password
                                            <?php if ($isEdit): ?>
                                                (leave blank to keep)
                                            <?php else: ?>
                                                <span class="text-danger">*</span>
                                            <?php endif; ?>
                                        </label>
                                        <input type="password" name="password" class="form-control"
                                               placeholder="<?php echo $isEdit ? '••••••••' : 'Min 6 chars'; ?>"
                                               <?php echo $isEdit ? '' : 'required'; ?>>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control"
                                               value="<?php echo e($data['phone']); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" class="form-control" rows="2"><?php echo e($data['address']); ?></textarea>
                                    </div>

                                    <!-- Job Information Section -->
                                    <div class="col-12 mt-2">
                                        <h6 class="form-section-title">Job Information</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Position <span class="text-danger">*</span></label>
                                        <input type="text" name="position" class="form-control"
                                               value="<?php echo e($data['position']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Department</label>
                                        <input type="text" name="department" class="form-control"
                                               value="<?php echo e($data['department']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Salary <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="salary" class="form-control"
                                                   step="0.01" min="0"
                                                   value="<?php echo e($data['salary']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Hire Date</label>
                                        <input type="date" name="hire_date" class="form-control"
                                               value="<?php echo e($data['hire_date']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Assign to Manager</label>
                                        <select name="manager_id" class="form-select">
                                            <option value="">— Unassigned —</option>
                                            <?php foreach ($managers as $m): ?>
                                            <option value="<?php echo $m['id']; ?>"
                                                    <?php echo ((int)$data['manager_id'] === (int)$m['id']) ? 'selected' : ''; ?>>
                                                <?php echo e($m['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Form Action Buttons -->
                                    <div class="col-12 d-flex gap-2 mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-1"></i>
                                            <?php echo $isEdit ? 'Save Changes' : 'Add Employee'; ?>
                                        </button>
                                        <a href="<?php echo BASE_URL; ?>/admin/employees.php"
                                           class="btn btn-outline-secondary">Cancel</a>
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
