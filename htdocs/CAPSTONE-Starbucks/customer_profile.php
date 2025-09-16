<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "First name, last name, and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        try {
            // Start transaction
            $connection->begin_transaction();
            
            // Check if email already exists for another customer
            $check_email = $connection->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
            $check_email->bind_param("si", $email, $customer_id);
            $check_email->execute();
            $email_exists = $check_email->get_result()->fetch_assoc();
            $check_email->close();
            
            if ($email_exists) {
                $error = "Email address is already in use by another account.";
            } else {
                // If password change is requested, verify current password
                if (!empty($new_password)) {
                    $verify_password = $connection->prepare("SELECT password FROM customers WHERE id = ?");
                    $verify_password->bind_param("i", $customer_id);
                    $verify_password->execute();
                    $result = $verify_password->get_result();
                    $customer = $result->fetch_assoc();
                    $verify_password->close();
                    
                    if (!password_verify($current_password, $customer['password'])) {
                        $error = "Current password is incorrect.";
                    } else {
                        // Update with new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_query = "UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ?, password = ? WHERE id = ?";
                        $stmt = $connection->prepare($update_query);
                        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $hashed_password, $customer_id);
                    }
                } else {
                    // Update without password change
                    $update_query = "UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?";
                    $stmt = $connection->prepare($update_query);
                    $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $customer_id);
                }
                
                if (!isset($error) || $error === '') {
                    if ($stmt->execute()) {
                        $connection->commit();
                        $message = "Profile updated successfully!";
                    } else {
                        $connection->rollback();
                        $error = "Failed to update profile. Please try again.";
                    }
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            $connection->rollback();
            $error = "An error occurred: " . $e->getMessage();
        }
    }
}

// Fetch current customer data
$stmt = $connection->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Starbucks</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #00704A, #008B5A);
            color: white;
            padding: 1rem 2rem 0.5rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo h1 {
            font-size: 1.8rem;
            margin-bottom: 0.2rem;
        }
        
        .customer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .menu-bar {
            display: flex;
            gap: 1rem;
            margin-top: 0.7rem;
            margin-bottom: 0.7rem;
            justify-content: flex-end;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .menu-link, .logout-btn, .primary-action-btn {
            text-decoration: none;
            border-radius: 5px;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            transition: background-color 0.3s;
            display: inline-block;
        }
        
        .menu-link {
            background: #17a2b8;
            color: white;
        }
        .menu-link:hover {
            background: #138496;
        }
        
        .edit-profile-link {
            background: #ffc107;
            color: #212529;
        }
        .edit-profile-link:hover {
            background: #e0a800;
        }
        
        .primary-action-btn {
            background: #00704A !important;
            font-size: 1.1rem !important;
            padding: 1rem 2rem !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 10px rgba(0, 112, 74, 0.3);
            transition: background-color 0.3s ease, transform 0.2s ease;
            color: white;
        }
        .primary-action-btn:hover {
            background: #005f3d !important;
            transform: translateY(-2px);
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
        }
        .logout-btn:hover {
            background: #c82333;
        }
        
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .profile-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #00704A;
        }
        
        .profile-header h2 {
            color: #00704A;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .profile-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #00704A;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .password-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            border-left: 4px solid #00704A;
        }
        
        .password-section h3 {
            color: #00704A;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #00704A;
            color: white;
        }
        .btn-primary:hover {
            background: #005f3d;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .required {
            color: #dc3545;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .status-blocked {
            background: #f5c6cb;
            color: #721c24;
        }

        .readonly-field {
            background: #f8f9fa !important;
            color: #666 !important;
            cursor: not-allowed;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .menu-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .container {
                margin: 1rem auto;
                padding: 0 0.5rem;
            }
            
            .profile-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>Edit Profile</h1>
                <p>Update your account information</p>
            </div>
            <div class="customer-info">
                <span>Welcome, <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>!</span>
            </div>
        </div>
        <!-- Menu Bar at the Top -->
        <nav class="menu-bar">
            <a href="customer_dashboard.php" class="primary-action-btn">← Back to Dashboard</a>
            <a href="customer_place_order.php" class="menu-link">Order a Product</a>
            <a href="customer_product.php" class="menu-link">View All Products</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>
    </header>

    <div class="container">
        <div class="profile-card">
            <div class="profile-header">
                <h2>📝 Edit Your Profile</h2>
                <p>Keep your information up to date for the best experience</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Personal Information -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                </div>

                <!-- Account Status Information (Read-only) -->
                <div class="form-group">
                    <label>Account Status</label>
                    <div style="display: flex; gap: 1rem; align-items: center; padding: 0.8rem; background: #f8f9fa; border-radius: 5px; border: 2px solid #e9ecef;">
                        <span style="font-weight: 500; color: #333;">Status:</span>
                        <span class="status-badge status-<?php echo $customer['status']; ?>" style="padding: 0.3rem 0.8rem; border-radius: 15px; font-size: 0.8rem; font-weight: bold;">
                            <?php echo ucfirst($customer['status']); ?>
                        </span>
                        <span style="font-weight: 500; color: #333;">Email Verified:</span>
                        <span style="color: <?php echo $customer['email_verified'] ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                            <?php echo $customer['email_verified'] ? 'Yes' : 'No'; ?>
                        </span>
                    </div>
                </div>

                <!-- Account Information (Read-only) -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Member Since</label>
                        <input type="text" class="readonly-field" value="<?php echo date('F j, Y', strtotime($customer['created_at'])); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Last Login</label>
                        <input type="text" class="readonly-field" value="<?php echo $customer['last_login'] ? date('F j, Y g:i A', strtotime($customer['last_login'])) : 'Never'; ?>" readonly>
                    </div>
                </div>

                <!-- Password Change Section -->
                <div class="password-section">
                    <h3>🔒 Change Password (Optional)</h3>
                    <p style="color: #666; margin-bottom: 1rem; font-size: 0.9rem;">Leave blank if you don't want to change your password</p>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" minlength="6">
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                    <a href="customer_dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Password validation
        document.getElementById('new_password').addEventListener('input', function() {
            const newPassword = this.value;
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword && confirmPassword.value) {
                if (newPassword === confirmPassword.value) {
                    confirmPassword.style.borderColor = '#28a745';
                } else {
                    confirmPassword.style.borderColor = '#dc3545';
                }
            }
        });
        
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword && confirmPassword) {
                if (newPassword === confirmPassword) {
                    this.style.borderColor = '#28a745';
                } else {
                    this.style.borderColor = '#dc3545';
                }
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;
            
            if (newPassword && !currentPassword) {
                e.preventDefault();
                alert('Please enter your current password to change it.');
                document.getElementById('current_password').focus();
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match.');
                document.getElementById('confirm_password').focus();
                return false;
            }
        });
    </script>
</body>
</html>