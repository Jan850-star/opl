<?php
// Test script to verify promo code system setup
session_start();
require_once 'db_connect.php';

echo "<h2>🔧 Promo Code System Test</h2>";

// Test 1: Check database connection
echo "<h3>1. Database Connection Test</h3>";
if (isset($connection)) {
    echo "✅ Connection variable exists<br>";
    if ($connection) {
        echo "✅ Database connection is active<br>";
    } else {
        echo "❌ Database connection failed<br>";
    }
} else {
    echo "❌ Connection variable not found<br>";
    // Try to create connection
    $host = '127.0.0.1';
    $db = 'mariadb';
    $user = 'mariadb';
    $pass = 'mariadb';
    $connection = mysqli_connect($host, $user, $pass, $db);
    if ($connection) {
        echo "✅ Created new connection successfully<br>";
    } else {
        echo "❌ Failed to create connection: " . mysqli_connect_error() . "<br>";
        exit;
    }
}

// Test 2: Check if promo_codes table exists
echo "<h3>2. Database Table Test</h3>";
$table_check = mysqli_query($connection, "SHOW TABLES LIKE 'promo_codes'");
if (mysqli_num_rows($table_check) > 0) {
    echo "✅ promo_codes table exists<br>";
} else {
    echo "❌ promo_codes table does not exist<br>";
    echo "Please run the create_promo_codes_table.sql script first!<br>";
}

// Test 3: Check table structure
echo "<h3>3. Table Structure Test</h3>";
$structure_check = mysqli_query($connection, "DESCRIBE promo_codes");
if ($structure_check) {
    echo "✅ Table structure is accessible<br>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = mysqli_fetch_assoc($structure_check)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Cannot access table structure: " . mysqli_error($connection) . "<br>";
}

// Test 4: Test insert operation
echo "<h3>4. Insert Test</h3>";
$test_code = "TEST" . time();
$test_query = "INSERT INTO promo_codes (code, description, discount_type, discount_value, min_order_amount, max_discount_amount, usage_limit, used_count, valid_from, valid_until, status, created_at, updated_at) VALUES (?, 'Test promo code', 'percentage', 10.00, 0.00, 0.00, 1, 0, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), 'active', NOW(), NOW())";

$stmt = mysqli_prepare($connection, $test_query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $test_code);
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Test insert successful<br>";
        $test_id = mysqli_insert_id($connection);
        echo "Test promo code created with ID: $test_id<br>";
        
        // Clean up test data
        mysqli_query($connection, "DELETE FROM promo_codes WHERE code = '$test_code'");
        echo "✅ Test data cleaned up<br>";
    } else {
        echo "❌ Test insert failed: " . mysqli_error($connection) . "<br>";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "❌ Prepare statement failed: " . mysqli_error($connection) . "<br>";
}

// Test 5: Check existing promo codes
echo "<h3>5. Existing Data Test</h3>";
$count_query = "SELECT COUNT(*) as count FROM promo_codes";
$count_result = mysqli_query($connection, $count_query);
if ($count_result) {
    $count = mysqli_fetch_assoc($count_result)['count'];
    echo "✅ Found $count existing promo codes in database<br>";
} else {
    echo "❌ Cannot count existing promo codes: " . mysqli_error($connection) . "<br>";
}

echo "<h3>🎯 Summary</h3>";
echo "If all tests passed, your promo code system should be working!<br>";
echo "<a href='admin_add_promo_code.php' style='background: #00704A; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 20px; display: inline-block;'>Go to Promo Code Admin</a>";
?>
