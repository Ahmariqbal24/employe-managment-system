<?php
// config/app.php
// Application-wide settings and helper functions used across all pages.

// --- App Settings ---
define('APP_NAME',       'EMS Pro');
define('APP_VERSION',    '1.0.0');
define('BASE_URL',       'http://localhost/emp_system'); // change this if needed
define('ITEMS_PER_PAGE', 8);  // how many rows to show per page


// =============================================
// SESSION HELPERS
// =============================================

// Start the session securely (only if not already started)
function session_boot() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configure the session cookie for security
        session_set_cookie_params(array(
            'lifetime' => 0,         // cookie disappears when browser closes
            'path'     => '/',
            'secure'   => false,     // set to true if your site uses HTTPS
            'httponly' => true,      // prevents JavaScript from reading the cookie
            'samesite' => 'Strict',  // prevents cross-site request forgery
        ));
        session_start();
    }
}


// =============================================
// FLASH MESSAGE HELPERS
// Flash messages are one-time notifications
// shown after a redirect (like "Saved!" or "Error!")
// =============================================

// Save a flash message to the session
// $type can be: 'success', 'danger', 'warning', 'info'
function flash($type, $message) {
    session_boot();
    // Store the message in the session as an array item
    $_SESSION['flash'][] = array('type' => $type, 'message' => $message);
}

// Display all stored flash messages and then clear them
function render_flash() {
    if (!empty($_SESSION['flash'])) {
        // Loop through each stored message
        foreach ($_SESSION['flash'] as $f) {
            $type = htmlspecialchars($f['type']);
            $msg  = htmlspecialchars($f['message']);
            // Print a Bootstrap alert div
            echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
            echo $msg;
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
        }
        // Clear all messages so they don't show again
        unset($_SESSION['flash']);
    }
}


// =============================================
// OUTPUT SANITIZATION
// =============================================

// Escape a value so it's safe to print inside HTML
// Always use this when showing data from the database or user input
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


// =============================================
// ACTIVITY LOGGING
// =============================================

// Save a record of what the user did (e.g. "added an employee")
function log_activity($action, $detail = '') {
    // Get the current user's ID from session (null if not logged in)
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    // Get the visitor's IP address
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

    // Insert a row into the activity_logs table
    $stmt = db()->prepare(
        'INSERT INTO activity_logs (user_id, action, detail, ip_address)
         VALUES (:uid, :action, :detail, :ip)'
    );
    $stmt->execute(array(
        ':uid'    => $userId,
        ':action' => $action,
        ':detail' => $detail,
        ':ip'     => $ip,
    ));
}


// =============================================
// REDIRECT
// =============================================

// Send the browser to a different page and stop running the current script
function redirect($url) {
    header('Location: ' . $url);
    exit;
}


// =============================================
// ROLE / USER HELPERS
// =============================================

// Return the current logged-in user's info array (from session)
function current_user() {
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    return array();
}

// Check if the logged-in user is an admin
function is_admin() {
    $user = current_user();
    return isset($user['role']) && $user['role'] === 'admin';
}

// Check if the logged-in user is a manager
function is_manager() {
    $user = current_user();
    return isset($user['role']) && $user['role'] === 'manager';
}

// Check if the logged-in user is an employee
function is_employee() {
    $user = current_user();
    return isset($user['role']) && $user['role'] === 'employee';
}

// Make sure a user is logged in; redirect to login page if not
function require_login() {
    session_boot();
    if (empty($_SESSION['user_id'])) {
        flash('warning', 'Please log in to continue.');
        redirect(BASE_URL . '/login.php');
    }
}

// Make sure the logged-in user has the right role
// $roles can be a string like 'admin' or an array like ['admin', 'manager']
function require_role($roles) {
    require_login();
    // Convert single string to array so we can use in_array for both cases
    $allowed = (array)$roles;
    // Get the current user's role from session
    $currentRole = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : '';
    // Check if their role is in the allowed list
    if (!in_array($currentRole, $allowed, true)) {
        flash('danger', 'Access denied.');
        redirect(BASE_URL . '/login.php');
    }
}


// =============================================
// PAGINATION HELPERS
// =============================================

// Calculate pagination values (which page we're on, how many pages total, etc.)
// Returns an array with 'offset', 'total_pages', and 'current_page'
function paginate($totalRows, $perPage = ITEMS_PER_PAGE) {
    // Get the page number from the URL (?page=2), default to page 1
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) {
        $page = 1; // never go below page 1
    }

    // Calculate how many pages there are in total
    $totalPages = (int)ceil($totalRows / $perPage);
    if ($totalPages < 1) {
        $totalPages = 1; // always at least 1 page
    }

    // Don't go past the last page
    if ($page > $totalPages) {
        $page = $totalPages;
    }

    // Calculate the row offset for the SQL LIMIT clause
    $offset = ($page - 1) * $perPage;

    return array(
        'offset'       => $offset,
        'total_pages'  => $totalPages,
        'current_page' => $page,
    );
}

// Print Bootstrap 5 pagination links
function render_pagination($totalPages, $current, $baseUrl) {
    // No need to render links if there's only one page
    if ($totalPages <= 1) {
        return;
    }

    echo '<nav><ul class="pagination justify-content-center">';

    // Loop from page 1 to the last page
    for ($i = 1; $i <= $totalPages; $i++) {
        // Highlight the current page
        $activeClass = ($i === $current) ? 'active' : '';

        // Build the URL for this page link
        // Check if there's already a ? in the URL to decide whether to use & or ?
        if (strpos($baseUrl, '?') !== false) {
            $url = $baseUrl . '&page=' . $i;
        } else {
            $url = $baseUrl . '?page=' . $i;
        }

        echo '<li class="page-item ' . $activeClass . '">';
        echo '<a class="page-link" href="' . e($url) . '">' . $i . '</a>';
        echo '</li>';
    }

    echo '</ul></nav>';
}
