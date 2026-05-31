<?php
// index.php
// This is the root entry point of the app.
// It checks if the user is logged in and redirects them to the right page.
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/app.php';

session_boot();

// If the user is logged in, go to their dashboard
if (!empty($_SESSION['user_id'])) {
    $role = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : 'employee';
    redirect(BASE_URL . '/' . $role . '/dashboard.php');
} else {
    // Otherwise, go to the login page
    redirect(BASE_URL . '/login.php');
}
