<?php
/**
 * Database Connection File
 * This file provides a consistent database connection across the application
 */

// Database configuration
$db_config = [
    'host' => '127.0.0.1',
    'dbname' => 'mariadb', // Update this to your actual database name
    'username' => 'mariadb', // Update this to your actual username
    'password' => 'mariadb', // Update this to your actual password
    'charset' => 'utf8mb4'
];

// Create PDO connection
try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
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
?>
