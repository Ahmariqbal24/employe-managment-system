<?php
// login.php
// This is the login page. Users enter their email and password here.
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/app.php';

session_boot();

// If the user is already logged in, send them to their dashboard
if (!empty($_SESSION['user_id'])) {
    $role = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : 'employee';
    redirect(BASE_URL . '/' . $role . '/dashboard.php');
}

// This array will hold any validation error messages
$errors = array();

// Only process the form if the page was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the submitted email and password, removing extra spaces
    $email    = trim(isset($_POST['email'])    ? $_POST['email']    : '');
    $password = trim(isset($_POST['password']) ? $_POST['password'] : '');

    // --- Basic Validation ---
    // Check that the email looks valid
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid e-mail address.';
    }
    // Check that the password is not empty
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    // Only try to log in if there are no errors so far
    if (empty($errors)) {
        // Look up the user in the database by email
        $stmt = db()->prepare(
            'SELECT id, name, email, password, role, is_active
               FROM users
              WHERE email = :email
              LIMIT 1'
        );
        $stmt->execute(array(':email' => $email));
        $user = $stmt->fetch(); // $user is now an array with the user's data (or false if not found)

        // Check: user exists AND account is active AND password matches
        if ($user && $user['is_active'] && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            // Store user info in the session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user']    = array(
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            );

            // Log this login event
            log_activity('LOGIN', 'User ' . $user['name'] . ' (' . $user['role'] . ') logged in.');

            // Show a welcome message and redirect to their dashboard
            flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect(BASE_URL . '/' . $user['role'] . '/dashboard.php');
        } else {
            // Invalid credentials
            $errors[] = 'Invalid credentials or account is inactive.';
            sleep(1); // small delay to slow down brute-force guessing
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – <?php echo APP_NAME; ?></title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body class="login-page">

<div class="login-wrapper">
    <!-- Left decorative panel (only visible on large screens) -->
    <div class="login-panel-left d-none d-lg-flex">
        <div class="login-panel-content">
            <div class="login-logo-big">
                <i class="bi bi-diagram-3-fill"></i>
            </div>
            <h1><?php echo APP_NAME; ?></h1>
            <p>Unified employee management for modern teams. Streamline HR, track performance, and collaborate effortlessly.</p>

            <div class="login-features mt-4">
                <div class="login-feature-item"><i class="bi bi-shield-check"></i> Role-Based Access Control</div>
                <div class="login-feature-item"><i class="bi bi-graph-up-arrow"></i> Analytics Dashboard</div>
                <div class="login-feature-item"><i class="bi bi-people"></i> Team Management</div>
                <div class="login-feature-item"><i class="bi bi-journal-text"></i> Activity Audit Logs</div>
            </div>
        </div>
    </div>

    <!-- Right panel: the login form -->
    <div class="login-panel-right">
        <div class="login-form-box">
            <div class="login-form-header">
                <!-- Show brand name on mobile only -->
                <div class="login-mobile-brand d-lg-none">
                    <i class="bi bi-diagram-3-fill"></i> <?php echo APP_NAME; ?>
                </div>
                <h2>Sign in</h2>
                <p>Enter your credentials to access your workspace.</p>
            </div>

            <!-- Show validation errors if any -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Show flash messages (e.g. "logged out successfully") -->
            <?php render_flash(); ?>

            <!-- Login form -->
            <form method="POST" action="" novalidate>
                <!-- Email field -->
                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="you@example.com"
                               value="<?php echo e(isset($_POST['email']) ? $_POST['email'] : ''); ?>"
                               required autocomplete="email">
                    </div>
                </div>

                <!-- Password field -->
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="••••••••"
                               required autocomplete="current-password">
                        <!-- Button to toggle password visibility -->
                        <button type="button" class="btn btn-outline-secondary" id="togglePass">
                            <i class="bi bi-eye" id="togglePassIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit button -->
                <button type="submit" class="btn btn-primary w-100 btn-lg">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                </button>
            </form>

            <!-- Demo credentials hint -->
            <div class="login-demo-hint mt-4">
                <strong>Demo accounts</strong> (password: <code>password</code>)<br>
                <span>Admin: <code>admin@ems.local</code></span><br>
                <span>Manager: <code>alice@ems.local</code></span><br>
                <span>Employee: <code>carol@ems.local</code></span>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle password show/hide when the eye button is clicked
    document.getElementById('togglePass').addEventListener('click', function () {
        var passwordInput = document.getElementById('password');
        var eyeIcon       = document.getElementById('togglePassIcon');

        if (passwordInput.type === 'password') {
            // Show the password
            passwordInput.type  = 'text';
            eyeIcon.className   = 'bi bi-eye-slash';
        } else {
            // Hide the password
            passwordInput.type  = 'password';
            eyeIcon.className   = 'bi bi-eye';
        }
    });
</script>
</body>
</html>
