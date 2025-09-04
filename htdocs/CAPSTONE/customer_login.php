<?php
// Start output buffering
ob_start();

// Start the session
session_start();

// Include the database connection file
include 'db_connect.php';

$error_message = '';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password'];

    // Add validation checks here
    if (empty($email) || empty($password)) {
        $error_message = "Please fill in all fields.";
    } else {
        // Check if the email exists
        try {
            $sql_check_email = "SELECT id, username, password, created_at FROM customers WHERE email = ?";
            $stmt_check = $pdo->prepare($sql_check_email);
            $stmt_check->execute([$email]);
            $user = $stmt_check->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Password is correct, set session variables
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $email;
                $_SESSION['created_at'] = $user['created_at'];

                // Redirect to home page
                header("Location: home.php");
                exit(); // Make sure to exit after redirection
            } else {
                $error_message = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            // Handle potential database errors
            $error_message = "Error: Could not log in. Please try again later.";
            // error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-6">Customer Login</h2>
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>
        <form action="customer_login.php" method="POST" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" id="email" name="email" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition-colors duration-200">Login</button>
        </form>
        <p class="mt-4 text-center text-sm text-gray-600">
            Don't have an account? <a href="customer_register.php" class="text-blue-600 hover:text-blue-800 font-semibold">Register here</a>
        </p>
    </div>
</body>
</html>
