<?php
// Script to manually insert a test promo code
require_once 'db_connect.php';

// Ensure we have a mysqli connection
if (!isset($connection)) {
    if (isset($conn)) {
        $connection = $conn;
    } elseif (isset($mysqli)) {
        $connection = $mysqli;
    } else {
        $host = '127.0.0.1';
        $db = 'mariadb';
        $user = 'mariadb';
        $pass = 'mariadb';
        $connection = mysqli_connect($host, $user, $pass, $db);
        if (!$connection) {
            die("Connection failed: " . mysqli_connect_error());
        }
    }
}

echo "<h2>🎟️ Insert Test Promo Code</h2>";

// Test promo code data
$test_code = "WELCOME20";
$test_description = "20% off for new customers";
$test_discount_type = "percentage";
$test_discount_value = 20.00;
$test_min_order = 50.00;
$test_max_discount = 100.00;
$test_usage_limit = 50;
$test_valid_from = date('Y-m-d H:i:s');
$test_valid_until = date('Y-m-d H:i:s', strtotime('+30 days'));
$test_status = "active";

// Check if promo code already exists
$check_query = "SELECT id FROM promo_codes WHERE code = ?";
$stmt = mysqli_prepare($connection, $check_query);
mysqli_stmt_bind_param($stmt, "s", $test_code);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    echo "⚠️ Promo code '$test_code' already exists. Skipping insertion.<br>";
} else {
    // Insert the test promo code
    $insert_query = "INSERT INTO promo_codes (code, description, discount_type, discount_value, min_order_amount, max_discount_amount, usage_limit, used_count, valid_from, valid_until, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, NOW(), NOW())";
    $stmt = mysqli_prepare($connection, $insert_query);
    
    if (!$stmt) {
        echo "❌ Prepare statement failed: " . mysqli_error($connection) . "<br>";
    } else {
        mysqli_stmt_bind_param($stmt, "sssddddsss", $test_code, $test_description, $test_discount_type, $test_discount_value, $test_min_order, $test_max_discount, $test_usage_limit, $test_valid_from, $test_valid_until, $test_status);
        
        if (mysqli_stmt_execute($stmt)) {
            $promo_id = mysqli_insert_id($connection);
            echo "✅ Test promo code created successfully!<br>";
            echo "ID: $promo_id<br>";
            echo "Code: $test_code<br>";
            echo "Description: $test_description<br>";
            echo "Discount: $test_discount_value% off<br>";
            echo "Min Order: ₱$test_min_order<br>";
            echo "Valid Until: $test_valid_until<br>";
        } else {
            echo "❌ Failed to create promo code: " . mysqli_error($connection) . "<br>";
        }
        mysqli_stmt_close($stmt);
    }
}

// Display all promo codes
echo "<h3>📋 All Promo Codes in Database:</h3>";
$display_query = "SELECT * FROM promo_codes ORDER BY created_at DESC";
$display_result = mysqli_query($connection, $display_query);

if ($display_result && mysqli_num_rows($display_result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='padding: 8px;'>ID</th>";
    echo "<th style='padding: 8px;'>Code</th>";
    echo "<th style='padding: 8px;'>Description</th>";
    echo "<th style='padding: 8px;'>Discount</th>";
    echo "<th style='padding: 8px;'>Min Order</th>";
    echo "<th style='padding: 8px;'>Status</th>";
    echo "<th style='padding: 8px;'>Created</th>";
    echo "</tr>";
    
    while ($promo = mysqli_fetch_assoc($display_result)) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . $promo['id'] . "</td>";
        echo "<td style='padding: 8px;'><strong>" . htmlspecialchars($promo['code']) . "</strong></td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($promo['description']) . "</td>";
        echo "<td style='padding: 8px;'>";
        if ($promo['discount_type'] == 'percentage') {
            echo $promo['discount_value'] . '%';
        } else {
            echo '₱' . number_format($promo['discount_value'], 2);
        }
        echo "</td>";
        echo "<td style='padding: 8px;'>₱" . number_format($promo['min_order_amount'], 2) . "</td>";
        echo "<td style='padding: 8px;'>" . $promo['status'] . "</td>";
        echo "<td style='padding: 8px;'>" . $promo['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "📝 No promo codes found in database.<br>";
}

echo "<br><a href='admin_add_promo_code.php' style='background: #00704A; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Panel</a>";
?>
