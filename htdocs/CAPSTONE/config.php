<?php
// config.php - Configuration file for Starbucks Management System

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USERNAME', 'mariadb');
define('DB_PASSWORD', 'mariadb');
define('DB_DATABASE', 'mariadb');

// Security Configuration
define('ADMIN_REGISTRATION_KEY', 'STARBUCKS_ADMIN_2024'); // Change this in production!

// Alternative: Use environment variables for better security
// define('ADMIN_REGISTRATION_KEY', getenv('ADMIN_KEY') ?: 'STARBUCKS_ADMIN_2024');

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

// Application Settings
define('SITE_NAME', 'Starbucks Management System');
define('SITE_URL', 'http://localhost/starbucks');

// Email Configuration (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');

// File Upload Settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH', 'uploads/');

// Pagination Settings
define('ORDERS_PER_PAGE', 20);
define('PRODUCTS_PER_PAGE', 24);

// Business Settings
define('DEFAULT_TAX_RATE', 8.25); // 8.25%
define('LOYALTY_POINTS_RATE', 1); // 1 point per $1 spent
define('MIN_DELIVERY_ORDER', 15.00);

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Timezone
date_default_timezone_set('America/New_York');

// Error Reporting (disable in production)
if (getenv('ENVIRONMENT') === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Function to get admin registration key
function getAdminRegistrationKey() {
    return ADMIN_REGISTRATION_KEY;
}

// Function to validate admin key
function validateAdminKey($provided_key) {
    return hash_equals(ADMIN_REGISTRATION_KEY, $provided_key);
}

// More secure admin keys for production
class AdminKeyManager {
    private static $valid_keys = [
        'STARBUCKS_MASTER_2024',
        'SB_REGION_001_2024',
        'SB_STORE_MANAGER_2024'
    ];
    
    private static $used_keys = []; // Track used keys
    
    public static function validateKey($key) {
        return in_array($key, self::$valid_keys) && !in_array($key, self::$used_keys);
    }
    
    public static function markKeyAsUsed($key) {
        if (!in_array($key, self::$used_keys)) {
            self::$used_keys[] = $key;
        }
    }
    
    public static function generateNewKey() {
        return 'SB_' . strtoupper(uniqid()) . '_' . date('Y');
    }
}

// Alternative: Database-stored admin keys (most secure)
function validateAdminKeyFromDatabase($connection, $provided_key) {
    $query = "SELECT id FROM admin_keys WHERE key_value = ? AND is_active = 1 AND expires_at > NOW()";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $provided_key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Mark key as used
        $update_query = "UPDATE admin_keys SET used_at = NOW(), used_by = ? WHERE key_value = ?";
        $update_stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ss", $_SERVER['REMOTE_ADDR'], $provided_key);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
        
        mysqli_stmt_close($stmt);
        return true;
    }
    
    mysqli_stmt_close($stmt);
    return false;
}
?>