<?php
session_start();
require_once 'db_connect.php';

$error = "";

// Redirect if already logged in as customer
if (isset($_SESSION['customer_id'])) {
    header("Location: customer_dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($connection, trim($_POST['email']));
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $error = "Both email and password are required.";
    } else {
        // Check customer credentials
        $query = "SELECT id, first_name, last_name, email, password FROM customers WHERE email = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['customer_id'] = $user['id'];
                $_SESSION['customer_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['customer_email'] = $user['email'];
                
                // Update last login
                $update_query = "UPDATE customers SET last_login = NOW() WHERE id = ?";
                $update_stmt = mysqli_prepare($connection, $update_query);
                mysqli_stmt_bind_param($update_stmt, "i", $user['id']);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
                
                header("Location: customer_dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
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
    <title>Customer Login - Starbucks Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #00704A, #008B5A);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo h1 {
            color: #00704A;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .logo p {
            color: #666;
            font-size: 0.9rem;
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
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #00704A;
        }
        
        .btn {
            width: 100%;
            padding: 0.8rem;
            background: #00704A;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-bottom: 1rem;
        }
        
        .btn:hover {
            background: #005A3C;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 0.8rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            text-align: center;
        }
        
        .links {
            text-align: center;
        }
        
        .links a {
            color: #00704A;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .forgot-password {
            text-align: center;
            margin: 1rem 0;
        }
        
        .forgot-password a {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Starbucks</h1>
            <p>Customer Login</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="forgot-password">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>
        
        <div class="links">
            <p>Don't have an account? <a href="customer_register.php">Register here</a></p>
            <p><a href="admin_login.php">Admin Login</a></p>
        </div>
    </div>
</body>
</html>