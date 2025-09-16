<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Validate POST data
if (!isset($_POST['product_id'], $_POST['quantity'])) {
    die("Invalid request.");
}

$product_id = intval($_POST['product_id']);
$quantity = intval($_POST['quantity']);

if ($quantity < 1) {
    die("Quantity must be at least 1.");
}

// Fetch product price and verify product exists and is active
$stmt = $connection->prepare("SELECT price, name FROM products WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Product not found or inactive.");
}

$product = $result->fetch_assoc();
$stmt->close();

$price = $product['price'];
$product_name = $product['name'];
$total_amount = $price * $quantity;
$tax_amount = 0.00; // Assuming no tax for simplicity or fetched from settings
$discount_amount = 0.00; // Assuming no discount for simplicity
$final_amount = $total_amount + $tax_amount - $discount_amount;

// Generate unique order number (e.g., ORD + timestamp + random)
$order_number = 'ORD' . time() . rand(1000, 9999);

// Insert new order
$stmt = $connection->prepare("INSERT INTO orders (customer_id, order_number, total_amount, tax_amount, discount_amount, final_amount, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())");
$stmt->bind_param("isddds", $customer_id, $order_number, $total_amount, $tax_amount, $discount_amount, $final_amount);
$success = $stmt->execute();

if (!$success) {
    die("Failed to create order: " . $stmt->error);
}

$order_id = $stmt->insert_id;
$stmt->close();

// Insert order item
$stmt = $connection->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iisid", $order_id, $product_id, $product_name, $quantity, $price);
$success = $stmt->execute();
$stmt->close();

if (!$success) {
    // Optionally, delete the order if order item insert fails
    $connection->query("DELETE FROM orders WHERE id = $order_id");
    die("Failed to add order item.");
}

// Redirect back with success message (you can handle messages via session or GET)
header("Location: customer_place_order.php?order=success");
exit();
?>