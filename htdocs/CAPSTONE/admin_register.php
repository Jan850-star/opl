<?php
session_start();
require_once 'db_connect.php';
require_once 'config.php'; // Include configuration file

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = mysqli_real_escape_string($connection, trim($_POST['first_name']));
    $last_name = mysqli_real_escape_string($connection, trim($_POST['last_name']));
    $email = mysqli_real_escape_string($connection, trim($_POST['email']));
    $phone = mysqli_real_escape_string($connection, trim($_POST['phone']));
    $employee_id = mysqli_real_escape_string($connection, trim($_POST['employee_id']));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $admin_key = trim($_POST['admin_key']);
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($employee_id) || empty($password) || empty($admin_key)) {
        $error = "All fields are required.";
    } elseif (!validateAdminKey($admin_key)) {
        $error = "Invalid admin registration key.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email or employee_id already exists
        $check_admin = "SELECT email FROM admins WHERE email = ? OR employee_id = ?";
        $stmt = mysqli_prepare($connection, $check_admin);
        mysqli_stmt_bind_param($stmt, "ss", $email, $employee_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Email or Employee ID already exists. Please use different credentials.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new admin
            $insert_query = "INSERT INTO admins (first_name, last_name, email, phone, employee_id, password, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($connection, $insert_query);
            mysqli_stmt_bind_param($stmt, "ssssss", $first_name, $last_name, $email, $phone, $employee_id, $hashed_password);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Admin registration successful! You can now login.";
                
                // Optional: Log the admin registration
                $log_query = "INSERT INTO audit_logs (user_type, user_id, action, ip_address, user_agent, created_at) VALUES ('admin', LAST_INSERT_ID(), 'admin_registered', ?, ?, NOW())";
                $log_stmt = mysqli_prepare($connection, $log_query);
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                mysqli_stmt_bind_param($log_stmt, "ss", $ip_address, $user_agent);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - <?php echo SITE_NAME; ?></title>
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
        input[type="tel"],
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
        input[type="tel"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #8B4513;
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
        
        .key-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .key-info code {
            background: #f8f9fa;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Starbucks</h1>
            <p>Admin Registration</p>
        </div>
        
        <div class="admin-note">
            <strong>🛡️ Admin Registration:</strong> You need a valid admin key to register as an administrator.
        </div>
        
        <div class="key-info">
            <strong>🔑 For Testing/Development:</strong><br>
            Use admin key: <code>STARBUCKS_ADMIN_2024</code><br>
            <small>⚠️ In production, contact your IT administrator for the current admin key.</small>
        </div>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" required 
                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" required 
                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" required 
                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="employee_id">Employee ID:</label>
                <input type="text" id="employee_id" name="employee_id" required 
                       value="<?php echo isset($_POST['employee_id']) ? htmlspecialchars($_POST['employee_id']) : ''; ?>"
                       placeholder="e.g., SB001">
            </div>
            
            <div class="form-group">
                <label for="admin_key">Admin Registration Key:</label>
                <input type="password" id="admin_key" name="admin_key" required 
                       placeholder="Enter admin registration key">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required 
                       minlength="8" placeholder="Minimum 8 characters">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       minlength="8" placeholder="Re-enter password">
            </div>
            
            <button type="submit" class="btn">🔐 Register Admin</button>
        </form>
        
        <div class="links">
            <p>Already have an admin account? <a href="admin_login.php">Login here</a></p>
            <p><a href="customer_register.php">Customer Registration</a></p>
        </div>
    </div>
</body>
</html>