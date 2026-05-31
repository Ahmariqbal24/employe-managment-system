<?php
// profile.php
// Shared profile page – all roles (admin, manager, employee) can update
// their own name, email, phone, address, and password.
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'My Profile';
$pdo       = db();
$userId    = (int)$_SESSION['user_id'];
$role      = $_SESSION['user']['role'];

// Load the current user's info plus their employee profile (if any)
$stmt = $pdo->prepare(
    "SELECT u.id, u.name, u.email, u.role,
            e.position, e.department, e.salary, e.phone, e.address, e.hire_date
       FROM users u
  LEFT JOIN employees e ON e.user_id = u.id
      WHERE u.id = :uid"
);
$stmt->execute(array(':uid' => $userId));
$profile = $stmt->fetch();

$errors  = array();

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read and clean the submitted values
    $name        = trim(isset($_POST['name'])         ? $_POST['name']         : '');
    $email       = trim(isset($_POST['email'])        ? $_POST['email']        : '');
    $phone       = trim(isset($_POST['phone'])        ? $_POST['phone']        : '');
    $address     = trim(isset($_POST['address'])      ? $_POST['address']      : '');
    $newPassword = trim(isset($_POST['new_password']) ? $_POST['new_password'] : '');
    $confirm     = trim(isset($_POST['confirm_pw'])   ? $_POST['confirm_pw']   : '');

    // --- Validation ---
    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email required.';
    }
    if ($newPassword !== '' && strlen($newPassword) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($newPassword !== '' && $newPassword !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Check that no other user already has this email
    if (empty($errors)) {
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
        $chk->execute(array(':email' => $email, ':id' => $userId));
        if ($chk->fetch()) {
            $errors[] = 'That email is already taken.';
        }
    }

    // --- Save if valid ---
    if (empty($errors)) {
        // Build the UPDATE query (conditionally add password field)
        $sql    = 'UPDATE users SET name=:name, email=:email, updated_at=NOW()';
        $params = array(':name' => $name, ':email' => $email, ':id' => $userId);

        // Only update password if a new one was typed
        if ($newPassword !== '') {
            $sql .= ', password=:pw';
            $params[':pw'] = password_hash($newPassword, PASSWORD_BCRYPT);
        }
        $sql .= ' WHERE id=:id';
        $pdo->prepare($sql)->execute($params);

        // Also update phone and address in the employee row (if it exists)
        $hasEmp = $pdo->prepare('SELECT id FROM employees WHERE user_id = :uid');
        $hasEmp->execute(array(':uid' => $userId));
        if ($hasEmp->fetch()) {
            $pdo->prepare('UPDATE employees SET phone=:phone, address=:addr WHERE user_id=:uid')
                ->execute(array(':phone' => $phone, ':addr' => $address, ':uid' => $userId));
        }

        // Update the session so the name shows correctly in the topbar
        $_SESSION['user']['name']  = $name;
        $_SESSION['user']['email'] = $email;

        log_activity('UPDATE_PROFILE', 'User updated their profile.');
        flash('success', 'Profile updated successfully.');
        redirect(BASE_URL . '/profile.php');
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="app-wrapper">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/includes/topbar.php'; ?>
        <main class="content-area">
            <?php render_flash(); ?>

            <div class="row justify-content-center">
                <div class="col-xl-7">

                    <!-- Profile header card (name, role, position) -->
                    <div class="card mb-4 text-center profile-hero-card">
                        <div class="card-body py-4">
                            <div class="profile-hero-avatar mb-3">
                                <?php echo strtoupper(substr(isset($profile['name']) ? $profile['name'] : 'U', 0, 1)); ?>
                            </div>
                            <h5 class="mb-1"><?php echo e(isset($profile['name']) ? $profile['name'] : ''); ?></h5>
                            <span class="badge-role badge-role--<?php echo e($role); ?>"><?php echo ucfirst($role); ?></span>
                            <?php if (isset($profile['position']) && $profile['position']): ?>
                            <p class="text-muted mt-2 mb-0">
                                <?php echo e($profile['position']); ?> · <?php echo e(isset($profile['department']) ? $profile['department'] : ''); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Edit Form -->
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-pencil-square me-2 text-primary"></i>
                            <strong>Edit Profile</strong>
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
                                    <div class="col-12"><h6 class="form-section-title">Basic Info</h6></div>

                                    <!-- Name -->
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" name="name" class="form-control"
                                               value="<?php echo e(isset($_POST['name']) ? $_POST['name'] : $profile['name']); ?>"
                                               required>
                                    </div>

                                    <!-- Email -->
                                    <div class="col-md-6">
                                        <label class="form-label">Email *</label>
                                        <input type="email" name="email" class="form-control"
                                               value="<?php echo e(isset($_POST['email']) ? $_POST['email'] : $profile['email']); ?>"
                                               required>
                                    </div>

                                    <!-- Phone -->
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control"
                                               value="<?php echo e(isset($_POST['phone']) ? $_POST['phone'] : (isset($profile['phone']) ? $profile['phone'] : '')); ?>">
                                    </div>

                                    <!-- Address -->
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" class="form-control" rows="2"><?php echo e(isset($_POST['address']) ? $_POST['address'] : (isset($profile['address']) ? $profile['address'] : '')); ?></textarea>
                                    </div>

                                    <!-- Password section -->
                                    <div class="col-12 mt-2">
                                        <h6 class="form-section-title">Change Password</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            New Password
                                            <small class="text-muted">(leave blank to keep current)</small>
                                        </label>
                                        <input type="password" name="new_password" class="form-control"
                                               placeholder="Min 6 characters">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" name="confirm_pw" class="form-control"
                                               placeholder="Repeat new password">
                                    </div>

                                    <!-- Buttons -->
                                    <div class="col-12 d-flex gap-2 mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg me-1"></i> Save Changes
                                        </button>
                                        <a href="javascript:history.back()" class="btn btn-outline-secondary">Back</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Read-only Job Details (shown for employee and manager roles) -->
                    <?php if ($role !== 'admin' && isset($profile['position']) && $profile['position']): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <i class="bi bi-briefcase me-2 text-info"></i>
                            <strong>Job Details</strong>
                            <small class="text-muted">(managed by admin)</small>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="detail-label">Position</label>
                                    <div class="detail-value"><?php echo e(isset($profile['position']) ? $profile['position'] : '—'); ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="detail-label">Department</label>
                                    <div class="detail-value"><?php echo e(isset($profile['department']) ? $profile['department'] : '—'); ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="detail-label">Salary</label>
                                    <div class="detail-value mono">$<?php echo number_format(isset($profile['salary']) ? $profile['salary'] : 0, 2); ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="detail-label">Hire Date</label>
                                    <div class="detail-value">
                                        <?php echo (isset($profile['hire_date']) && $profile['hire_date']) ? date('F d, Y', strtotime($profile['hire_date'])) : '—'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
