<?php
// includes/auth.php
// Include this file at the top of every page that requires the user to be logged in.
// It loads the config files and starts the session.
//
// Usage:
//   require_once __DIR__ . '/../includes/auth.php';
//   require_role('admin');  // optional: restrict to specific role

// Load config files (order matters - db.php first, then app.php)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';

// Start the session
session_boot();

// Redirect to login if not logged in
require_login();
