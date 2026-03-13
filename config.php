<?php
// Database Configuration for Personal Budget Manager

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Default XAMPP MySQL password is empty
define('DB_NAME', 'budget_manager');

// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die(json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $conn->connect_error
        ]));
    }
    
    // Set charset to utf8mb4 for proper character handling
    $conn->set_charset('utf8mb4');
    
    return $conn;
}

// Session configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('REMEMBER_ME_DURATION', 2592000); // 30 days in seconds

// Nepal specific constants
define('CURRENCY', 'NPR');
define('SSF_RATE', 0.11); // 11% SSF contribution (combined employer + employee)
define('EMPLOYEE_SSF_RATE', 0.055); // 5.5% employee contribution

// Nepal Income Tax Slabs (FY 2024-25)
$TAX_SLABS = [
    ['min' => 0, 'max' => 500000, 'rate' => 0.01],
    ['min' => 500001, 'max' => 700000, 'rate' => 0.10],
    ['min' => 700001, 'max' => 1000000, 'rate' => 0.20],
    ['min' => 1000001, 'max' => 2000000, 'rate' => 0.30],
    ['min' => 2000001, 'max' => PHP_INT_MAX, 'rate' => 0.36]
];

// Security settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('ACCOUNT_LOCK_DURATION', 900); // 15 minutes in seconds

// Enable error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Note: Content-Type header should be set per file, not globally

