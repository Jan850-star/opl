<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php'; // Include configuration file

$error = "";
$success = "";

// Redirect if already logged in
if (isset($_SESSION['admin_id']) && $_SESSION['user_type'] == 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_identifier = mysqli_real_escape_string($connection, trim($_POST['login_identifier']));
    $password = trim($_POST['password']);
    $remember_me = isset($_POST['remember_me']);
    
    // Validation
    if (empty($login_identifier) || empty($password)) {
        $error = "Email/Employee ID and password are required.";
    } else {
        // Check admin credentials (allow login with either email or employee_id)
        $login_query = "SELECT id, first_name, last_name, email, employee_id, password, role, status, last_login FROM admins WHERE (email = ? OR employee_id = ?) AND status = 'active'";
        $stmt = mysqli_prepare($connection, $login_query);
        mysqli_stmt_bind_param($stmt, "ss", $login_identifier, $login_identifier);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($admin = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $admin['password'])) {
                // Login successful
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_employee_id'] = $admin['employee_id'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['user_type'] = 'admin';
                
                // Update last login (your table has last_login as timestamp)
                $update_login = "UPDATE admins SET last_login = NOW() WHERE id = ?";
                $update_stmt = mysqli_prepare($connection, $update_login);
                mysqli_stmt_bind_param($update_stmt, "i", $admin['id']);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
                
                // Set remember me cookie if requested (optional - only if table exists)
                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (30 * 24 * 60 * 60); // 30 days
                    
                    // Check if admin_remember_tokens table exists
                    $table_check = mysqli_query($connection, "SHOW TABLES LIKE 'admin_remember_tokens'");
                    if (mysqli_num_rows($table_check) > 0) {
                        $token_query = "INSERT INTO admin_remember_tokens (admin_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?)) ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
                        $token_stmt = mysqli_prepare($connection, $token_query);
                        if ($token_stmt) {
                            mysqli_stmt_bind_param($token_stmt, "isi", $admin['id'], $token, $expires);
                            mysqli_stmt_execute($token_stmt);
                            mysqli_stmt_close($token_stmt);
                            
                            // Set cookie
                            setcookie('admin_remember', $token, $expires, '/', '', true, true);
                        }
                    }
                }
                
                // Log the login (optional - only if table exists)
                $table_check = mysqli_query($connection, "SHOW TABLES LIKE 'audit_logs'");
                if (mysqli_num_rows($table_check) > 0) {
                    $log_query = "INSERT INTO audit_logs (user_type, user_id, action, ip_address, user_agent, created_at) VALUES ('admin', ?, 'admin_login', ?, ?, NOW())";
                    $log_stmt = mysqli_prepare($connection, $log_query);
                    if ($log_stmt) {
                        $ip_address = $_SERVER['REMOTE_ADDR'];
                        $user_agent = $_SERVER['HTTP_USER_AGENT'];
                        mysqli_stmt_bind_param($log_stmt, "iss", $admin['id'], $ip_address, $user_agent);
                        mysqli_stmt_execute($log_stmt);
                        mysqli_stmt_close($log_stmt);
                    }
                }
                
                // Redirect to dashboard
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error = "Invalid password. Please try again.";
                
                // Log failed login attempt (optional - only if table exists)
                $table_check = mysqli_query($connection, "SHOW TABLES LIKE 'audit_logs'");
                if (mysqli_num_rows($table_check) > 0) {
                    $fail_log_query = "INSERT INTO audit_logs (user_type, user_id, action, ip_address, user_agent, details, created_at) VALUES ('admin', ?, 'failed_login', ?, ?, 'Invalid password', NOW())";
                    $fail_log_stmt = mysqli_prepare($connection, $fail_log_query);
                    if ($fail_log_stmt) {
                        mysqli_stmt_bind_param($fail_log_stmt, "iss", $admin['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                        mysqli_stmt_execute($fail_log_stmt);
                        mysqli_stmt_close($fail_log_stmt);
                    }
                }
            }
        } else {
            $error = "Admin account not found or inactive.";
            
            // Log failed login attempt with unknown user (optional - only if table exists)
            $table_check = mysqli_query($connection, "SHOW TABLES LIKE 'audit_logs'");
            if (mysqli_num_rows($table_check) > 0) {
                $fail_log_query = "INSERT INTO audit_logs (user_type, user_id, action, ip_address, user_agent, details, created_at) VALUES ('admin', NULL, 'failed_login', ?, ?, CONCAT('Unknown user: ', ?), NOW())";
                $fail_log_stmt = mysqli_prepare($connection, $fail_log_query);
                if ($fail_log_stmt) {
                    mysqli_stmt_bind_param($fail_log_stmt, "sss", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $login_identifier);
                    mysqli_stmt_execute($fail_log_stmt);
                    mysqli_stmt_close($fail_log_stmt);
                }
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle logout message
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $success = "You have been successfully logged out.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #8B4513, #A0522D);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo h1 {
            color: #8B4513;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .logo p {
            color: #666;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #8B4513;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 0.5rem;
            transform: scale(1.2);
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
            cursor: pointer;
        }
        
        .btn {
            width: 100%;
            padding: 0.8rem;
            background: #8B4513;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background: #704010;
        }
        
        .message {
            padding: 0.8rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            text-align: center;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .links {
            text-align: center;
            margin-top: 1rem;
        }
        
        .links a {
            color: #8B4513;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .admin-note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .login-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .role-display {
            background: #e2e3e5;
            border: 1px solid #d3d6d8;
            color: #495057;
            padding: 0.6rem;
            border-radius: 5px;
            margin-top: 1rem;
            font-size: 0.85rem;
            text-align: center;
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 0.5rem;
        }
        
        .forgot-password a {
            color: #6c757d;
            font-size: 0.9rem;
            text-decoration: none;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .security-notice {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 0.6rem;
            border-radius: 5px;
            margin-top: 1rem;
            font-size: 0.85rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Starbucks</h1>
            <p>Admin Portal</p>
        </div>
        
        <div class="admin-note">
            <strong>🛡️ Administrator Access:</strong> This portal is restricted to authorized administrators only.
        </div>
        
        <div class="login-info">
            <strong>📧 Login Options:</strong><br>
            You can login using either your <strong>email address</strong> or your <strong>employee ID</strong>.
        </div>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="login_identifier">Email or Employee ID:</label>
                <input type="text" id="login_identifier" name="login_identifier" required 
                       value="<?php echo isset($_POST['login_identifier']) ? htmlspecialchars($_POST['login_identifier']) : ''; ?>"
                       placeholder="Enter your email or employee ID">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your password">
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="remember_me" name="remember_me">
                <label for="remember_me">Remember me for 30 days</label>
            </div>
            
            <button type="submit" class="btn">🔐 Login</button>
            
            <div class="forgot-password">
                <a href="admin_forgot_password.php">Forgot your password?</a>
            </div>
        </form>
        
        <div class="links">
            <p>Need an admin account? <a href="admin_register.php">Register here</a></p>
            <p><a href="customer_login.php">Customer Login</a> | <a href="index.php">Back to Home</a></p>
        </div>
        
        <div class="role-display">
            <strong>Available Roles:</strong> Super Admin • Admin • Manager • Staff
        </div>
        
        <div class="security-notice">
            ⚠️ <strong>Security Notice:</strong> All login attempts are logged and monitored for security purposes.
        </div>
    </div>

    <script>
        // Auto-focus on login field when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('login_identifier').focus();
        });
        
        // Show/hide password toggle functionality (optional)
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleBtn = document.querySelector('.password-toggle');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordField.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const loginIdentifier = document.getElementById('login_identifier').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!loginIdentifier || !password) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return false;
            }
        });
        
        // Enhanced security - clear form on page unload
        window.addEventListener('beforeunload', function() {
            document.getElementById('password').value = '';
        });
        
        // Prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>