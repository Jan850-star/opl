<?php
// Test script to verify promo code creation and display
session_start();
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

echo "<h2>🎟️ Promo Code Display Test</h2>";

// Test 1: Check if we can fetch promo codes
echo "<h3>1. Fetching Promo Codes</h3>";
$promo_query = "SELECT * FROM promo_codes ORDER BY created_at DESC LIMIT 10";
$promo_result = mysqli_query($connection, $promo_query);

if (!$promo_result) {
    echo "❌ Query failed: " . mysqli_error($connection) . "<br>";
} else {
    $row_count = mysqli_num_rows($promo_result);
    echo "✅ Query successful! Found $row_count promo codes.<br>";
    
    if ($row_count > 0) {
        echo "<h4>📋 Promo Codes in Database:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 8px;'>ID</th>";
        echo "<th style='padding: 8px;'>Code</th>";
        echo "<th style='padding: 8px;'>Description</th>";
        echo "<th style='padding: 8px;'>Discount Type</th>";
        echo "<th style='padding: 8px;'>Discount Value</th>";
        echo "<th style='padding: 8px;'>Status</th>";
        echo "<th style='padding: 8px;'>Created</th>";
        echo "</tr>";
        
        while ($promo = mysqli_fetch_assoc($promo_result)) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . $promo['id'] . "</td>";
            echo "<td style='padding: 8px;'><strong>" . htmlspecialchars($promo['code']) . "</strong></td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($promo['description']) . "</td>";
            echo "<td style='padding: 8px;'>" . $promo['discount_type'] . "</td>";
            echo "<td style='padding: 8px;'>" . $promo['discount_value'] . "</td>";
            echo "<td style='padding: 8px;'>" . $promo['status'] . "</td>";
            echo "<td style='padding: 8px;'>" . $promo['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "📝 No promo codes found in database.<br>";
    }
}

// Test 2: Try to create a test promo code
echo "<h3>2. Creating Test Promo Code</h3>";
$test_code = "TEST" . time();
$test_description = "Test promo code for display verification";
$test_discount_type = "percentage";
$test_discount_value = 10.00;
$test_min_order = 0.00;
$test_max_discount = 0.00;
$test_usage_limit = 100;
$test_valid_from = date('Y-m-d H:i:s');
$test_valid_until = date('Y-m-d H:i:s', strtotime('+30 days'));
$test_status = "active";

$insert_query = "INSERT INTO promo_codes (code, description, discount_type, discount_value, min_order_amount, max_discount_amount, usage_limit, used_count, valid_from, valid_until, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, NOW(), NOW())";
$stmt = mysqli_prepare($connection, $insert_query);

if (!$stmt) {
    echo "❌ Prepare statement failed: " . mysqli_error($connection) . "<br>";
} else {
    mysqli_stmt_bind_param($stmt, "sssddddsss", $test_code, $test_description, $test_discount_type, $test_discount_value, $test_min_order, $test_max_discount, $test_usage_limit, $test_valid_from, $test_valid_until, $test_status);
    
    if (mysqli_stmt_execute($stmt)) {
        $test_id = mysqli_insert_id($connection);
        echo "✅ Test promo code created successfully with ID: $test_id<br>";
        echo "Code: <strong>$test_code</strong><br>";
    } else {
        echo "❌ Failed to create test promo code: " . mysqli_error($connection) . "<br>";
    }
    mysqli_stmt_close($stmt);
}

// Test 3: Verify the new promo code appears
echo "<h3>3. Verifying New Promo Code</h3>";
$verify_query = "SELECT * FROM promo_codes WHERE code = ?";
$stmt = mysqli_prepare($connection, $verify_query);
mysqli_stmt_bind_param($stmt, "s", $test_code);
mysqli_stmt_execute($stmt);
$verify_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($verify_result) > 0) {
    $verify_promo = mysqli_fetch_assoc($verify_result);
    echo "✅ Test promo code found in database!<br>";
    echo "ID: " . $verify_promo['id'] . "<br>";
    echo "Code: " . $verify_promo['code'] . "<br>";
    echo "Description: " . $verify_promo['description'] . "<br>";
    echo "Created: " . $verify_promo['created_at'] . "<br>";
} else {
    echo "❌ Test promo code not found in database!<br>";
}
mysqli_stmt_close($stmt);

// Clean up test data
echo "<h3>4. Cleaning Up Test Data</h3>";
$cleanup_query = "DELETE FROM promo_codes WHERE code = ?";
$stmt = mysqli_prepare($connection, $cleanup_query);
mysqli_stmt_bind_param($stmt, "s", $test_code);
if (mysqli_stmt_execute($stmt)) {
    echo "✅ Test data cleaned up successfully<br>";
} else {
    echo "❌ Failed to clean up test data: " . mysqli_error($connection) . "<br>";
}
mysqli_stmt_close($stmt);

echo "<h3>🎯 Next Steps</h3>";
echo "1. If all tests passed, your promo code system is working correctly<br>";
echo "2. Try creating a promo code through the admin interface<br>";
echo "3. Check the admin page with ?debug=1 to see debug information<br>";
echo "<br>";
echo "<a href='admin_add_promo_code.php' style='background: #00704A; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Admin Panel</a>";
echo "<a href='admin_add_promo_code.php?debug=1' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Panel (Debug)</a>";
?>
