<?php
// Test script for stock update functionality
require_once 'db_connect.php';

echo "<h2>Stock Update Test Script</h2>";

// Test database connection
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "<p>✅ Database connection successful</p>";

// Test 1: Check if products table exists and has data
$test_query = "SELECT COUNT(*) as count FROM products";
$result = mysqli_query($connection, $test_query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "<p>✅ Products table accessible. Total products: " . $row['count'] . "</p>";
} else {
    echo "<p>❌ Products table query failed: " . mysqli_error($connection) . "</p>";
}

// Test 2: Check a specific product's current stock
$product_id = 17; // Blueberry Muffin
$check_query = "SELECT id, name, stock_quantity, status FROM products WHERE id = ?";
$stmt = mysqli_prepare($connection, $check_query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($product) {
        echo "<p>✅ Product found: " . $product['name'] . " (ID: " . $product['id'] . ")</p>";
        echo "<p>Current stock: " . $product['stock_quantity'] . "</p>";
        echo "<p>Status: " . $product['status'] . "</p>";
        
        // Test 3: Try to update stock
        $add_quantity = 5;
        $new_stock = $product['stock_quantity'] + $add_quantity;
        
        echo "<p><strong>Testing stock update...</strong></p>";
        echo "<p>Adding $add_quantity units. New stock should be: $new_stock</p>";
        
        // Start transaction
        mysqli_begin_transaction($connection);
        
        try {
            // Update stock
            $update_query = "UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE id = ?";
            $update_stmt = mysqli_prepare($connection, $update_query);
            
            if (!$update_stmt) {
                throw new Exception("Failed to prepare update statement: " . mysqli_error($connection));
            }
            
            mysqli_stmt_bind_param($update_stmt, "ii", $new_stock, $product_id);
            $update_result = mysqli_stmt_execute($update_stmt);
            
            if (!$update_result) {
                throw new Exception("Failed to execute update: " . mysqli_stmt_error($update_stmt));
            }
            
            $affected_rows = mysqli_stmt_affected_rows($update_stmt);
            mysqli_stmt_close($update_stmt);
            
            if ($affected_rows == 0) {
                throw new Exception("No rows were affected");
            }
            
            // Verify the update
            $verify_query = "SELECT stock_quantity FROM products WHERE id = ?";
            $verify_stmt = mysqli_prepare($connection, $verify_query);
            mysqli_stmt_bind_param($verify_stmt, "i", $product_id);
            mysqli_stmt_execute($verify_stmt);
            $verify_result = mysqli_stmt_get_result($verify_stmt);
            $verify_data = mysqli_fetch_assoc($verify_result);
            mysqli_stmt_close($verify_stmt);
            
            $actual_new_stock = intval($verify_data['stock_quantity']);
            
            if ($actual_new_stock == $new_stock) {
                echo "<p>✅ Stock update successful! New stock: $actual_new_stock</p>";
                mysqli_commit($connection);
            } else {
                throw new Exception("Stock verification failed. Expected: $new_stock, Actual: $actual_new_stock");
            }
            
        } catch (Exception $e) {
            mysqli_rollback($connection);
            echo "<p>❌ Stock update failed: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p>❌ Product with ID $product_id not found</p>";
    }
} else {
    echo "<p>❌ Failed to prepare statement: " . mysqli_error($connection) . "</p>";
}

// Test 4: Check MySQL user permissions
echo "<p><strong>Checking MySQL user permissions...</strong></p>";
$permissions_query = "SHOW GRANTS FOR CURRENT_USER()";
$permissions_result = mysqli_query($connection, $permissions_query);
if ($permissions_result) {
    echo "<p>Current user permissions:</p><ul>";
    while ($row = mysqli_fetch_row($permissions_result)) {
        echo "<li>" . htmlspecialchars($row[0]) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ Could not check permissions: " . mysqli_error($connection) . "</p>";
}

// Test 5: Check table structure
echo "<p><strong>Checking products table structure...</strong></p>";
$structure_query = "DESCRIBE products";
$structure_result = mysqli_query($connection, $structure_query);
if ($structure_result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($structure_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Could not check table structure: " . mysqli_error($connection) . "</p>";
}

mysqli_close($connection);
?>
