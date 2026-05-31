<?php
// employee/dashboard.php
// Dashboard for the Employee role – shows their own profile info.
require_once __DIR__ . '/../includes/auth.php';
require_role('employee');

$pageTitle = 'My Dashboard';
$pdo       = db();
$userId    = (int)$_SESSION['user_id'];

// Load this employee's full record (including manager info)
$stmt = $pdo->prepare(
    "SELECT u.name, u.email, u.created_at,
            e.position, e.department, e.salary, e.phone, e.address, e.hire_date,
            m.name AS manager_name, m.email AS manager_email
       FROM users u
       JOIN employees e ON e.user_id = u.id
  LEFT JOIN users m ON m.id = e.manager_id
      WHERE u.id = :uid"
);
$stmt->execute(array(':uid' => $userId));
$profile = $stmt->fetch();

if (!$profile) {
    flash('danger', 'Profile record not found. Contact admin.');
}

// Calculate years of service from hire date
$hireDate    = isset($profile['hire_date']) ? $profile['hire_date'] : null;
$yearsServed = null;
if ($hireDate) {
    // Divide seconds since hire date by seconds in a year
    $yearsServed = floor((time() - strtotime($hireDate)) / 31536000);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="app-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>
        <main class="content-area">
            <?php render_flash(); ?>

            <div class="row g-4">
                <!-- Profile Summary Card (left column) -->
                <div class="col-lg-4">
                    <div class="card text-center profile-hero-card">
                        <div class="card-body py-5">
                            <!-- Big avatar with first letter -->
                            <div class="profile-hero-avatar">
                                <?php echo strtoupper(substr(isset($profile['name']) ? $profile['name'] : 'U', 0, 1)); ?>
                            </div>
                            <h4 class="mt-3 mb-1"><?php echo e(isset($profile['name']) ? $profile['name'] : ''); ?></h4>
                            <p class="text-muted mb-2"><?php echo e(isset($profile['position']) ? $profile['position'] : ''); ?></p>
                            <span class="badge bg-info"><?php echo e(isset($profile['department']) ? $profile['department'] : 'No Dept'); ?></span>
                            <hr class="my-3">
                            <!-- Salary and tenure side by side -->
                            <div class="d-flex justify-content-around text-center">
                                <div>
                                    <div class="fw-bold fs-5">$<?php echo number_format(isset($profile['salary']) ? $profile['salary'] : 0, 0); ?></div>
                                    <div class="small text-muted">Salary</div>
                                </div>
                                <div>
                                    <div class="fw-bold fs-5"><?php echo $yearsServed !== null ? $yearsServed . ' yr' : '—'; ?></div>
                                    <div class="small text-muted">Tenure</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="<?php echo BASE_URL; ?>/profile.php" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-pencil me-1"></i> Edit My Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Details (right column) -->
                <div class="col-lg-8">
                    <!-- Personal Info Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="bi bi-info-circle me-2 text-primary"></i>
                            <strong>My Information</strong>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="detail-label">Full Name</label>
                                    <div class="detail-value"><?php echo e(isset($profile['name']) ? $profile['name'] : '—'); ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="detail-label">Email</label>
                                    <div class="detail-value"><?php echo e(isset($profile['email']) ? $profile['email'] : '—'); ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="detail-label">Phone</label>
                                    <div class="detail-value"><?php echo e(isset($profile['phone']) ? $profile['phone'] : '—'); ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="detail-label">Hire Date</label>
                                    <div class="detail-value">
                                        <?php echo $hireDate ? date('F d, Y', strtotime($hireDate)) : '—'; ?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="detail-label">Address</label>
                                    <div class="detail-value"><?php echo e(isset($profile['address']) ? $profile['address'] : '—'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Manager Info Card (only shown if a manager is assigned) -->
                    <?php if (isset($profile['manager_name']) && $profile['manager_name']): ?>
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-person-badge me-2 text-purple"></i>
                            <strong>My Manager</strong>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="manager-card-avatar" style="font-size:1.5rem;width:56px;height:56px;">
                                    <?php echo strtoupper(substr($profile['manager_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?php echo e($profile['manager_name']); ?></div>
                                    <div class="text-muted"><?php echo e(isset($profile['manager_email']) ? $profile['manager_email'] : ''); ?></div>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
