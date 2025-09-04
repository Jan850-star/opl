<?php
// Start the session at the very beginning of the file, before any HTML output.
session_start();

// Use 'require_once' to ensure the database connection is loaded successfully.
// This will halt the script if the file cannot be found, preventing the 'undefined variable' error.
require_once 'db_connect.php';

$success_message = '';
$error_message = '';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password'];

    // Add validation checks here
    if (empty($username) || empty($email) || empty($password)) {
        $error_message = "Please fill in all fields.";
    } else {
        // Check if the email already exists to prevent duplicate accounts
        try {
            // Use the TABLE_CUSTOMERS constant to build the query
            $sql_check_email = "SELECT id FROM " . TABLE_CUSTOMERS . " WHERE email = ?";
            $stmt_check = $pdo->prepare($sql_check_email);
            $stmt_check->execute([$email]);
            $user_exists = $stmt_check->fetch();

            if ($user_exists) {
                $error_message = "This email is already registered. Please login or use a different email.";
            } else {
                // Hash the password for security
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Prepare and execute an SQL INSERT statement
                // Use the TABLE_CUSTOMERS constant here as well
                $sql_insert = "INSERT INTO " . TABLE_CUSTOMERS . " (username, email, password) VALUES (?, ?, ?)";
                $stmt_insert = $pdo->prepare($sql_insert);
                $stmt_insert->execute([$username, $email, $hashed_password]);

                $success_message = "Registration successful! You can now login.";

                // Redirect the user to the login page immediately after registration
                header("Location: customer_login.php");
                exit(); // Make sure to exit after header redirection
            }
        } catch (PDOException $e) {
            // Handle potential database errors
            $error_message = "Error: Could not register user. Please try again later.";
            // In a production environment, you should log the error
            // error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-6">Customer Register</h2>
        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $success_message; ?></span>
            </div>
        <?php elseif (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>
        <form action="customer_register.php" method="POST" class="space-y-4">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" id="username" name="username" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" id="email" name="email" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" required class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-md transition-colors duration-200">Register</button>
        </form>
        <p class="mt-4 text-center text-sm text-gray-600">
            Already have an account? <a href="customer_login.php" class="text-blue-600 hover:text-blue-800 font-semibold">Login here</a>
        </p>
    </div>
</body>
</html>
