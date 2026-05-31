<?php
// logout.php
// Logs out the user by destroying their session and redirecting to login.
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/app.php';

session_boot();

// Only log the activity if someone is actually logged in
if (!empty($_SESSION['user_id'])) {
    log_activity('LOGOUT', 'User logged out.');
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie from the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),  // name of the cookie
        '',              // empty value
        time() - 42000, // a time in the past (this deletes it)
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session on the server
session_destroy();

// Show a success message and go back to the login page
flash('success', 'You have been logged out successfully.');
redirect(BASE_URL . '/login.php');
