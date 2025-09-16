<?php
session_start();
require_once 'promo_code_functions.php';

// Database connection settings
$host = '127.0.0.1';
$db   = 'mariadb';
$user = 'mariadb';
$pass = 'mariadb';
$charset = 'utf8mb4';

// Set up DSN and connect
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo "Database connection failed.";
    exit;
}

// Also create mysqli connection for promo code functions
$connection = mysqli_connect($host, $user, $pass, $db);
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_promo_submit'])) {
    $promo_code = trim(strtoupper($_POST['promo_code']));
    $description = trim($_POST['description']);
    $discount_type = $_POST['discount_type'];
    $discount_value = floatval($_POST['discount_value']);
    $min_order_amount = floatval($_POST['min_order_amount']);
    $max_discount_amount = floatval($_POST['max_discount_amount']);
    $usage_limit = intval($_POST['usage_limit']);
    $valid_from = $_POST['valid_from'];
    $valid_until = $_POST['valid_until'];
    $status = $_POST['status'];
    
    // Validation
    $errors = [];
    
    if (empty($promo_code)) {
        $errors[] = "Promo code is required.";
    } elseif (strlen($promo_code) < 3) {
        $errors[] = "Promo code must be at least 3 characters long.";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required.";
    }
    
    if ($discount_value <= 0) {
        $errors[] = "Discount value must be greater than 0.";
    }
    
    if ($discount_type == 'percentage' && $discount_value > 100) {
        $errors[] = "Percentage discount cannot exceed 100%.";
    }
    
    if ($min_order_amount < 0) {
        $errors[] = "Minimum order amount cannot be negative.";
    }
    
    if ($max_discount_amount < 0) {
        $errors[] = "Maximum discount amount cannot be negative.";
    }
    
    if ($usage_limit <= 0) {
        $errors[] = "Usage limit must be greater than 0.";
    }
    
    if (strtotime($valid_from) >= strtotime($valid_until)) {
        $errors[] = "Valid until date must be after valid from date.";
    }
    
    // Check if promo code already exists
    if (empty($errors)) {
        $check_query = "SELECT id FROM promo_codes WHERE code = ?";
        $stmt = $pdo->prepare($check_query);
        $stmt->execute([$promo_code]);
        if ($stmt->fetch()) {
            $errors[] = "Promo code already exists.";
        }
    }
    
    // Insert promo code if no errors
    if (empty($errors)) {
        try {
            $insert_query = "INSERT INTO promo_codes 
                (code, description, discount_type, discount_value, min_order_amount, 
                 max_discount_amount, usage_limit, valid_from, valid_until, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($insert_query);
            $stmt->execute([
                $promo_code, $description, $discount_type, $discount_value, 
                $min_order_amount, $max_discount_amount, $usage_limit, 
                $valid_from, $valid_until, $status
            ]);
            
            $success_message = "Promo code '{$promo_code}' created successfully!";
            
            // Clear form data
            $_POST = [];
            
        } catch (PDOException $e) {
            $error_message = "Error creating promo code: " . $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Get recent promo codes for display
$recent_promos_query = "SELECT * FROM promo_codes ORDER BY created_at DESC LIMIT 10";
$promo_result = mysqli_query($connection, $recent_promos_query);
if (!$promo_result) {
    $promo_result = false;
}
?>
