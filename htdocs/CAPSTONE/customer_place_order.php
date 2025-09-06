<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Fetch customer's name from the database
$stmt_customer = $connection->prepare("SELECT first_name, last_name FROM customers WHERE id = ?");
$stmt_customer->bind_param("i", $customer_id);
$stmt_customer->execute();
$result_customer = $stmt_customer->get_result();
$customer_data = $result_customer->fetch_assoc();
$stmt_customer->close();

$customer_username = htmlspecialchars($customer_data['first_name'] . ' ' . $customer_data['last_name']);

// Initialize cart session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    if ($product_id > 0 && $quantity > 0) {
        // Get product details
        $stmt_product = $connection->prepare("SELECT name, price FROM products WHERE id = ? AND status = 'active'");
        $stmt_product->bind_param("i", $product_id);
        $stmt_product->execute();
        $product_result = $stmt_product->get_result();
        
        if ($product_result->num_rows > 0) {
            $product = $product_result->fetch_assoc();
            
            // Add to cart or update quantity
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $quantity
                ];
            }
            
            $success_message = "Item added to cart successfully!";
        }
        $stmt_product->close();
    }
}

// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_from_cart') {
    $product_id = intval($_POST['product_id']);
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $success_message = "Item removed from cart!";
    }
}

// Handle update cart quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_cart') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    if (isset($_SESSION['cart'][$product_id])) {
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
        $success_message = "Cart updated!";
    }
}

// Handle place order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    if (!empty($_SESSION['cart'])) {
        try {
            $connection->begin_transaction();
            
            // Calculate totals
            $subtotal = 0;
            foreach ($_SESSION['cart'] as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            
            $tax_rate = 0.08; // 8% tax
            $tax_amount = $subtotal * $tax_rate;
            $final_amount = $subtotal + $tax_amount;
            
            // Generate order number
            $order_number = 'SB' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Insert order
            $stmt_order = $connection->prepare("INSERT INTO orders (customer_id, order_number, total_amount, tax_amount, final_amount, status, payment_status, order_type) VALUES (?, ?, ?, ?, ?, 'pending', 'pending', 'takeaway')");
            $stmt_order->bind_param("isddd", $customer_id, $order_number, $subtotal, $tax_amount, $final_amount);
            $stmt_order->execute();
            $order_id = $connection->insert_id;
            $stmt_order->close();
            
            // Insert order items
            $stmt_item = $connection->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($_SESSION['cart'] as $product_id => $item) {
                $total_price = $item['price'] * $item['quantity'];
                $stmt_item->bind_param("iisidi", $order_id, $product_id, $item['name'], $item['quantity'], $item['price'], $total_price);
                $stmt_item->execute();
            }
            $stmt_item->close();
            
            $connection->commit();
            
            // Clear cart
            $_SESSION['cart'] = [];
            
            $success_message = "Order placed successfully! Order Number: #" . $order_number;
            
        } catch (Exception $e) {
            $connection->rollback();
            $error_message = "Error placing order. Please try again.";
        }
    } else {
        $error_message = "Your cart is empty!";
    }
}

// Fetch active products
$products_query = "SELECT id, name, description, price, image_url FROM products WHERE status = 'active' ORDER BY name ASC";
$products_result = mysqli_query($connection, $products_query);

if (!$products_result) {
    die("Database query failed: " . mysqli_error($connection));
}

$products = mysqli_fetch_all($products_result, MYSQLI_ASSOC);

// Calculate cart totals
$cart_subtotal = 0;
$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_subtotal += $item['price'] * $item['quantity'];
    $cart_count += $item['quantity'];
}
$cart_tax = $cart_subtotal * 0.08;
$cart_total = $cart_subtotal + $cart_tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Place Order - Starbucks Customer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
            margin: auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #00704a;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        h2, h3 {
            color: #00704a;
            margin: 0 0 15px 0;
        }
        .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .nav-link, .logout-btn {
            background-color: #00704a;
            color: #fff;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .nav-link:hover, .logout-btn:hover {
            background-color: #005f3d;
        }
        .logout-btn {
            background-color: #d32f2f;
        }
        .logout-btn:hover {
            background-color: #b71c1c;
        }
        
        /* Main layout */
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        /* Product grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 20px;
        }
        .product-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .product-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background-color: #eee;
            border-bottom: 1px solid #f0f0f0;
        }
        .product-content {
            padding: 1rem 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .product-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #00704a;
            margin-bottom: 0.5rem;
        }
        .product-description {
            font-size: 0.9rem;
            color: #555;
            line-height: 1.4;
            margin-bottom: 1rem;
            flex-grow: 1;
        }
        .product-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
        }
        .add-to-cart-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .quantity-input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        .add-btn {
            background-color: #00704a;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        .add-btn:hover {
            background-color: #005f3d;
        }
        
        /* Cart sidebar */
        .cart-sidebar {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #00704a;
        }
        .cart-count {
            background-color: #00704a;
            color: white;
            border-radius: 50%;
            padding: 5px 10px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item-details h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 0.9rem;
        }
        .cart-item-price {
            color: #666;
            font-size: 0.8rem;
        }
        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cart-quantity {
            width: 40px;
            padding: 3px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-align: center;
            font-size: 0.8rem;
        }
        .remove-btn {
            background-color: #d32f2f;
            color: white;
            border: none;
            padding: 5px 8px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.7rem;
        }
        .remove-btn:hover {
            background-color: #b71c1c;
        }
        .cart-summary {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #00704a;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .summary-total {
            font-weight: bold;
            font-size: 1.1rem;
            color: #00704a;
        }
        .place-order-btn {
            width: 100%;
            background-color: #00704a;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px;
            transition: background-color 0.3s;
        }
        .place-order-btn:hover {
            background-color: #005f3d;
        }
        .place-order-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        /* Messages */
        .message {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .empty-cart {
            text-align: center;
            color: #666;
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            .cart-sidebar {
                position: static;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Place Your Order</h2>
            <div class="nav-links">
                <a href="customer_dashboard.php" class="nav-link">Dashboard</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <p>Welcome, <strong><?php echo $customer_username; ?></strong>!</p>
        
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <div class="main-content">
            <!-- Products Section -->
            <div class="products-section">
                <h3>Available Products</h3>
                <?php if (!empty($products)): ?>
                    <div class="product-grid">
                        <?php 
                        $image_map = [
                            'Blueberry Muffin' => '/CAPSTONE/Blueberry_Muffin.jpg',
                            'Butter Croissant' => '/CAPSTONE/Butter_Croissant.jpg',
                            'Turkey & Swiss Sandwich' => '/CAPSTONE/Turkey_Swiss_Sandwich.jpg',
                            'Pike Place Roast' => '/CAPSTONE/Pike_Place_Roast.jpg',
                            'Caffè Americano' => '/CAPSTONE/CaffeAmericano.jpg',
                            'Cappuccino' => '/CAPSTONE/Cappuccino.jpg',
                            'Caffè Latte' => '/CAPSTONE/CaffeLatte.jpg',
                            'Caramel Macchiato' => '/CAPSTONE/Caramel_Macchiato.jpg',
                            'Iced Coffee' => '/CAPSTONE/Iced_Coffee.jpg',
                            'Cold Brew Coffee' => '/CAPSTONE/Cold_Brew_Coffee.jpg',
                            'Iced Caffè Americano' => '/CAPSTONE/Iced_Caffe_Americano.jpg',
                            'Iced Caramel Macchiato' => '/CAPSTONE/Iced_Caramel_Macchiato.jpg',
                            'Caramel Frappuccino' => '/CAPSTONE/Caramel_Frappuccino.jpg',
                            'Mocha Frappuccino' => '/CAPSTONE/Mocha_Frappuccino.jpg',
                            'Vanilla Bean Frappuccino' => '/CAPSTONE/Vanill-Bean_Frappuccino.jpg',
                            'Earl Grey Tea' => '/CAPSTONE/Earl_Grey_Tea.jpg',
                            'Green Tea' => '/CAPSTONE/Green_Tea.jpg',
                            'Chai Tea Latte' => '/CAPSTONE/Chai_Tea_Latte.jpg',
                        ];
                        ?>
                        <?php foreach ($products as $product): ?>
                            <?php 
                                $image_src = 'https://via.placeholder.com/200x200?text=No+Image';
                                if (!empty($product['image_url'])) {
                                    $image_src = htmlspecialchars($product['image_url']);
                                } elseif (array_key_exists($product['name'], $image_map)) {
                                    $image_src = $image_map[$product['name']];
                                }
                            ?>
                            <div class="product-card">
                                <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image" />
                                <div class="product-content">
                                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div class="product-description"><?php echo htmlspecialchars($product['description'] ?? 'No description available.'); ?></div>
                                    <div class="product-price">₱<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></div>
                                    
                                    <form method="POST" class="add-to-cart-form">
                                        <input type="hidden" name="action" value="add_to_cart">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="number" name="quantity" min="1" value="1" class="quantity-input" required>
                                        <button type="submit" class="add-btn">Add to Cart</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No active products available at the moment. Please check back later!</p>
                <?php endif; ?>
            </div>
            
            <!-- Cart Sidebar -->
            <div class="cart-sidebar">
                <div class="cart-header">
                    <h3>Your Cart</h3>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </div>
                
                <?php if (empty($_SESSION['cart'])): ?>
                    <div class="empty-cart">
                        <p>Your cart is empty</p>
                    </div>
                <?php else: ?>
                    <div class="cart-items">
                        <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                            <div class="cart-item">
                                <div class="cart-item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <div class="cart-item-price">₱<?php echo number_format($item['price'], 2); ?> each</div>
                                </div>
                                <div class="cart-item-controls">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_cart">
                                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="0" class="cart-quantity" 
                                               onchange="this.form.submit()">
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove_from_cart">
                                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                        <button type="submit" class="remove-btn">Remove</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>₱<?php echo number_format($cart_subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (8%):</span>
                            <span>₱<?php echo number_format($cart_tax, 2); ?></span>
                        </div>
                        <div class="summary-row summary-total">
                            <span>Total:</span>
                            <span>₱<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="place_order">
                            <button type="submit" class="place-order-btn">Place Order</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>