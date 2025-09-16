<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['admin_id'])) {
    // No active session, redirect to appropriate login page
    header("Location: customer_login.php");
    exit();
}

// Store user type and name for logging
$user_type = '';
$user_name = '';
$redirect_url = '';

if (isset($_SESSION['customer_id'])) {
    $user_type = 'customer';
    $user_name = isset($_SESSION['customer_name']) ? $_SESSION['customer_name'] : 'Customer';
    $redirect_url = 'customer_login.php';
} elseif (isset($_SESSION['admin_id'])) {
    $user_type = 'admin';
    $user_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';
    $redirect_url = 'admin_login.php';
}

// Log the logout action (optional - requires database connection)
try {
    require_once 'db_connect.php';
    
    // Insert logout log into audit_logs table
    $log_query = "INSERT INTO audit_logs (user_type, user_id, action, ip_address, user_agent, created_at) VALUES (?, ?, 'logout', ?, ?, NOW())";
    $stmt = $connection->prepare($log_query);
    
    $user_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : $_SESSION['admin_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt->bind_param("siss", $user_type, $user_id, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
} catch (Exception $e) {
    // Log error but don't stop logout process
    error_log("Logout logging failed: " . $e->getMessage());
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any additional cookies if they exist
setcookie('remember_token', '', time() - 3600, '/');
setcookie('user_preferences', '', time() - 3600, '/');

// Redirect to appropriate login page with success message
$message = urlencode("You have been successfully logged out. Thank you for using our system!");
header("Location: $redirect_url?logout=success&message=$message");
exit();
?>