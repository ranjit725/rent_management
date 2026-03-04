<?php
// Show errors (since personal project)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// --------------------
// Database Credentials
// --------------------
define("DB_HOST", "localhost");
define("DB_NAME", "rental_management");
define("DB_USER", "root");
define("DB_PASSWORD", "");

// Include DB & functions
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Global DB instance
$GLOBALS['db'] = DB::getInstance();
?>