<?php
/**
 * Database Connection File
 * This file provides a consistent database connection across the application
 * Chowking Management System
 */

// Database configuration
$db_config = [
    'host' => '127.0.0.1',
    'dbname' => 'mariadb', // Updated database name for Chowking system
    'username' => 'mariadb', // Default XAMPP/WAMP username
    'password' => 'mariadb', // Default XAMPP/WAMP password (empty)
    'charset' => 'utf8mb4'
];

// Create PDO connection
try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set timezone
    $pdo->exec("SET time_zone = '+08:00'"); // Philippine timezone
    
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Optional: Create a MySQLi connection if needed for legacy code
try {
    $connection = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['dbname']);
    if ($connection->connect_error) {
        throw new Exception("MySQLi connection failed: " . $connection->connect_error);
    }
    $connection->set_charset($db_config['charset']);
} catch(Exception $e) {
    // MySQLi connection failed, but PDO is available
    $connection = null;
}

// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to generate random token
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check if user is admin
function is_admin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Function to redirect user
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to display success message
function show_success($message) {
    return "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                <i class='fas fa-check-circle me-2'></i>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

// Function to display error message
function show_error($message) {
    return "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                <i class='fas fa-exclamation-circle me-2'></i>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

// Function to format currency
function format_currency($amount) {
    return '₱' . number_format($amount, 2);
}

// Function to format date
function format_date($date) {
    return date('M d, Y', strtotime($date));
}

// Function to format datetime
function format_datetime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}
?>
