<?php
// config/db.php
// This file connects to the database.
// Change the values below to match your own database settings.

// --- Database Settings ---
define('DB_HOST',    'localhost');       // usually localhost
define('DB_NAME',    'emp_management'); // your database name
define('DB_USER',    'root');           // your database username
define('DB_PASS',    '');               // your database password (empty for XAMPP default)
define('DB_CHARSET', 'utf8mb4');        // character encoding

// --- Database Connection Function ---
// This function returns a database connection (PDO object).
// We store it in a static variable so we only connect once.
function db() {
    // $pdo holds our connection. static means it remembers its value between calls.
    static $pdo = null;

    // Only create a new connection if we don't already have one
    if ($pdo === null) {
        // Build the connection string (called a DSN)
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        // PDO options: show errors, return rows as arrays, use real prepared statements
        $options = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        );

        // Try to connect; if it fails, show a friendly error instead of crashing
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log the real error (not shown to users)
            error_log('DB Connection failed: ' . $e->getMessage());
            // Show a simple message to the user
            die('<h3 style="font-family:sans-serif;color:#c0392b;padding:2rem;">
                  Database connection failed. Please check your configuration.
                 </h3>');
        }
    }

    return $pdo;
}
