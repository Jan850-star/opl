<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: customer_login.php"); // Redirect to login if not logged in
    exit();
}

// Get the username from the session
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Welcome to Our Application</h1>
            <nav>
                <a href="customer_register.php" class="text-white hover:underline">Register</a>
                <a href="customer_login.php" class="ml-4 text-white hover:underline">Login</a>
                <a href="logout.php" class="ml-4 text-white hover:underline">Logout</a>
            </nav>
        </div>
    </header>

    <main class="flex-grow container mx-auto p-6">
        <h2 class="text-3xl font-bold mb-4">Hello, <?php echo htmlspecialchars($username); ?>!</h2>
        <p class="text-lg mb-4">Welcome to your dashboard. Here you can manage your account and access various features.</p>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold mb-2">Your Account Details</h3>
            <p class="mb-2"><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
            <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); // Assuming you store email in session ?></p>
            <p class="mb-2"><strong>Account Created On:</strong> <?php echo htmlspecialchars($_SESSION['created_at']); // Assuming you store created_at in session ?></p>
        </div>
    </main>

    <footer class="bg-gray-800 text-white p-4 text-center">
        <p>&copy; <?php echo date("Y"); ?> Your Company Name. All rights reserved.</p>
    </footer>
</body>
</html>
