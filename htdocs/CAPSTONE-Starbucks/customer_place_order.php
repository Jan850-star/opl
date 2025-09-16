<?php
session_start();
require_once 'db_connect.php';

// Inline receipt rendering when requested
if (isset($_GET['receipt']) && intval($_GET['receipt']) === 1) {
    // Require login for viewing receipts
    if (!isset($_SESSION['customer_id'])) {
        header("Location: customer_login.php");
        exit();
    }
    $current_customer_id = intval($_SESSION['customer_id']);
    $order_id = intval($_GET['order_id'] ?? 0);
    if ($order_id <= 0) { die('Invalid order'); }

    // Load order
    $stmt = $connection->prepare("SELECT o.*, c.first_name, c.last_name, c.email FROM orders o JOIN customers c ON c.id = o.customer_id WHERE o.id = ? AND o.customer_id = ? LIMIT 1");
    $stmt->bind_param('ii', $order_id, $current_customer_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$order) { die('Order not found'); }

    // Load items
    $items = [];
    $stmtI = $connection->prepare("SELECT product_name, quantity, unit_price, total_price, selected_size FROM order_items WHERE order_id = ? ORDER BY id ASC");
    $stmtI->bind_param('i', $order_id);
    $stmtI->execute();
    $items = $stmtI->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $stmtI->close();

    // Store info
    $store = [ 'name' => 'Store', 'address' => '', 'phone' => '' ];
    $res = $connection->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('store_name','store_address','store_phone')");
    while ($row = $res->fetch_assoc()) {
        if ($row['setting_key'] === 'store_name') $store['name'] = $row['setting_value'];
        if ($row['setting_key'] === 'store_address') $store['address'] = $row['setting_value'];
        if ($row['setting_key'] === 'store_phone') $store['phone'] = $row['setting_value'];
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Receipt #<?php echo htmlspecialchars($order['order_number']); ?></title>
  <style>
    body{font-family:Segoe UI,Tahoma,Geneva,Verdana,sans-serif;background:#f6f7fb;margin:0;padding:20px}
    .paper{max-width:800px;margin:0 auto;background:#fff;border-radius:12px;box-shadow:0 8px 25px rgba(0,0,0,.08);padding:24px}
    .header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #00704a;padding-bottom:12px;margin-bottom:16px}
    .title{color:#00704a;margin:0}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #eee;text-align:left}
    th{background:#f8f9fa}
    .summary{margin-top:12px}
    .row{display:flex;justify-content:space-between;margin:6px 0}
    .total{font-weight:700;color:#00704a;font-size:18px}
    .print{margin-top:16px;display:flex;gap:10px;flex-wrap:wrap}
    .btn{background:#00704a;color:#fff;border:none;padding:10px 16px;border-radius:8px;cursor:pointer;transition:all 0.3s ease}
    .btn:hover{background:#005a3c;transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,112,74,0.3)}
    .btn-secondary{background:#6c757d;color:#fff}
    .btn-secondary:hover{background:#545b62}
    .navigation-menu{background:rgba(255,255,255,0.95);backdrop-filter:blur(10px);padding:1rem 0;box-shadow:0 2px 20px rgba(0,0,0,0.1);border-bottom:2px solid #00704a;margin-bottom:20px}
    .nav-content{max-width:800px;margin:0 auto;padding:0 2rem;display:flex;justify-content:center;gap:1rem;flex-wrap:wrap}
    .nav-btn{background:linear-gradient(135deg,#00704a,#28a745);color:white;padding:0.75rem 1.5rem;border-radius:25px;text-decoration:none;font-weight:500;transition:all 0.3s;display:flex;align-items:center;gap:0.5rem;box-shadow:0 4px 15px rgba(0,112,74,0.3)}
    .nav-btn:hover{background:linear-gradient(135deg,#005a3c,#1e7e34);transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,112,74,0.4);color:white;text-decoration:none}
  </style>
  <script>function printPage(){window.print()}</script>
</head>
<body>
  <!-- Navigation Menu for Receipt Page -->
  <div class="navigation-menu">
    <div class="nav-content">
      <a href="customer_dashboard.php" class="nav-btn">
        🏠 Dashboard
      </a>
      <a href="customer_place_order.php" class="nav-btn">
        🛒 Place New Order
      </a>
      <a href="customer_view_products.php" class="nav-btn">
        📋 Browse Products
      </a>
      <a href="customer_digital_wallet.php" class="nav-btn">
        💳 Digital Wallet
      </a>
    </div>
  </div>
  
  <div class="paper">
    <div class="header">
      <div>
        <h2 class="title"><?php echo htmlspecialchars($store['name']); ?></h2>
        <div><?php echo htmlspecialchars($store['address']); ?></div>
        <div><?php echo htmlspecialchars($store['phone']); ?></div>
      </div>
      <div>
        <div><strong>Order:</strong> #<?php echo htmlspecialchars($order['order_number']); ?></div>
        <div><strong>Date:</strong> <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($order['created_at']))); ?></div>
        <div><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></div>
        <div><strong>Payment:</strong> <?php echo htmlspecialchars($order['payment_method'] ?? ''); ?></div>
      </div>
    </div>
    <div style="margin-bottom:12px;">
      <div><strong>Customer:</strong> <?php echo htmlspecialchars($order['first_name'].' '.$order['last_name']); ?> (<?php echo htmlspecialchars($order['email']); ?>)</div>
    </div>
    <table>
      <thead>
        <tr>
          <th>Item</th>
          <th>Size</th>
          <th>Qty</th>
          <th>Unit</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><?php echo htmlspecialchars($it['product_name']); ?></td>
            <td><?php echo htmlspecialchars($it['selected_size'] ?? ''); ?></td>
            <td><?php echo (int)$it['quantity']; ?></td>
            <td>₱<?php echo number_format((float)$it['unit_price'],2); ?></td>
            <td>₱<?php echo number_format((float)$it['total_price'],2); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="summary">
      <div class="row"><span>Subtotal</span><span>₱<?php echo number_format((float)$order['total_amount'],2); ?></span></div>
      <div class="row"><span>Discount</span><span>-₱<?php echo number_format((float)$order['discount_amount'],2); ?></span></div>
      <div class="row"><span>Tax</span><span>₱<?php echo number_format((float)$order['tax_amount'],2); ?></span></div>
      <div class="row total"><span>Total</span><span>₱<?php echo number_format((float)$order['final_amount'],2); ?></span></div>
    </div>
    <div class="print">
      <button class="btn" onclick="printPage()">🖨️ Print Receipt</button>
      <a href="customer_dashboard.php" class="btn btn-secondary">🏠 Back to Dashboard</a>
    </div>
  </div>
</body>
</html>
<?php
    exit();
}

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

// Get wallet balance
$wallet_balance = 0.00;
try {
    $stmt_wallet = $connection->prepare("SELECT balance FROM digital_wallet WHERE customer_id = ?");
    $stmt_wallet->bind_param("i", $customer_id);
    $stmt_wallet->execute();
    $wallet_result = $stmt_wallet->get_result();
    if ($wallet_result->num_rows > 0) {
        $wallet_data = $wallet_result->fetch_assoc();
        $wallet_balance = $wallet_data['balance'];
    }
    $stmt_wallet->close();
} catch (Exception $e) {
    // Wallet table might not exist yet, continue with 0 balance
    error_log("Digital wallet table not found: " . $e->getMessage());
    $wallet_balance = 0.00;
}

// Initialize cart session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Initialize promo session bucket
if (!isset($_SESSION['applied_promo'])) {
    $_SESSION['applied_promo'] = null;
}

// Helper: load tax rate from settings (fallback 8%)
$tax_rate_percent = 8.0;
$stmt_tax = $connection->prepare("SELECT setting_value FROM settings WHERE setting_key = 'tax_rate' LIMIT 1");
if ($stmt_tax) {
    $stmt_tax->execute();
    $tax_res = $stmt_tax->get_result();
    if ($tax_res && $row = $tax_res->fetch_assoc()) {
        $tax_rate_percent = floatval($row['setting_value']);
    }
    $stmt_tax->close();
}

// Helper: load loyalty points rate (points per currency unit)
$loyalty_rate = 0.0;
$stmt_lp = $connection->prepare("SELECT setting_value FROM settings WHERE setting_key = 'loyalty_points_rate' LIMIT 1");
if ($stmt_lp) {
    $stmt_lp->execute();
    $lp_res = $stmt_lp->get_result();
    if ($lp_res && $row = $lp_res->fetch_assoc()) {
        $loyalty_rate = floatval($row['setting_value']);
    }
    $stmt_lp->close();
}

// Helper: compute promo discount given current cart
function compute_promo_discount(mysqli $connection, array $cart, array $promo): array {
    // Returns [eligible_subtotal, discount_amount]
    $eligible_subtotal = 0.0;
    if (empty($cart)) {
        return [0.0, 0.0];
    }
    
    // For promo_codes table, we'll apply discount to entire cart (simplified approach)
    foreach ($cart as $cart_key => $item) {
        $eligible_subtotal += $item['price'] * $item['quantity'];
    }

    $discount = 0.0;
    if ($promo['discount_type'] === 'percentage') {
        $discount = ($eligible_subtotal * floatval($promo['discount_value'])) / 100.0;
    } elseif ($promo['discount_type'] === 'fixed') {
        $discount = floatval($promo['discount_value']);
        // Cap at eligible subtotal
        $discount = min($discount, $eligible_subtotal);
    }

    if (!is_null($promo['max_discount_amount']) && $promo['max_discount_amount'] > 0) {
        $discount = min($discount, floatval($promo['max_discount_amount']));
    }

    // Ensure non-negative
    if ($discount < 0) { $discount = 0.0; }
    return [$eligible_subtotal, $discount];
}


// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $selected_size = $_POST['size'] ?? 'Medium';
    
    if ($product_id > 0 && $quantity > 0) {
        // Get product details including stock and size pricing
        $stmt_product = $connection->prepare("
            SELECT p.name, p.price, p.stock_quantity, ps.size_price_modifier 
            FROM products p 
            LEFT JOIN product_sizes ps ON p.id = ps.product_id AND ps.size_name = ? 
            WHERE p.id = ? AND p.status = 'active'
        ");
        $stmt_product->bind_param("si", $selected_size, $product_id);
        $stmt_product->execute();
        $product_result = $stmt_product->get_result();
        
        if ($product_result->num_rows > 0) {
            $product = $product_result->fetch_assoc();
            $available_stock = intval($product['stock_quantity']);
            $size_modifier = floatval($product['size_price_modifier'] ?? 0);
            $final_price = floatval($product['price']) + $size_modifier;
            
            // Check if product is in stock
            if ($available_stock <= 0) {
                $error_message = "Sorry, this product is currently out of stock.";
            } else {
                // Create unique cart key with product_id and size
                $cart_key = $product_id . '_' . $selected_size;
                
                // Calculate total quantity if item already in cart
                $current_cart_quantity = isset($_SESSION['cart'][$cart_key]) ? $_SESSION['cart'][$cart_key]['quantity'] : 0;
                $total_quantity = $current_cart_quantity + $quantity;
                
                // Check if requested quantity exceeds available stock
                if ($total_quantity > $available_stock) {
                    $error_message = "Sorry, only {$available_stock} items are available in stock. You already have {$current_cart_quantity} in your cart.";
                } else {
                    // Add to cart or update quantity
                    if (isset($_SESSION['cart'][$cart_key])) {
                        $_SESSION['cart'][$cart_key]['quantity'] += $quantity;
                    } else {
                        $_SESSION['cart'][$cart_key] = [
                            'product_id' => $product_id,
                            'name' => $product['name'],
                            'price' => $final_price,
                            'quantity' => $quantity,
                            'size' => $selected_size
                        ];
                    }
                    
                    $success_message = "Item added to cart successfully!";
                }
            }
        } else {
            $error_message = "Product not found or not available.";
        }
        $stmt_product->close();
    } else {
        $error_message = "Invalid quantity. Please enter a valid amount.";
    }
}

// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_from_cart') {
    $cart_key = $_POST['cart_key'] ?? '';
    if (isset($_SESSION['cart'][$cart_key])) {
        unset($_SESSION['cart'][$cart_key]);
        $success_message = "Item removed from cart!";
    }
}

// Handle update cart quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_cart') {
    $cart_key = $_POST['cart_key'] ?? '';
    $quantity = intval($_POST['quantity']);
    
    if (isset($_SESSION['cart'][$cart_key])) {
        if ($quantity > 0) {
            // Extract product_id from cart_key
            $product_id = intval(explode('_', $cart_key)[0]);
            
            // Check stock availability before updating
            $stmt_stock = $connection->prepare("SELECT stock_quantity FROM products WHERE id = ? AND status = 'active'");
            $stmt_stock->bind_param("i", $product_id);
            $stmt_stock->execute();
            $stock_result = $stmt_stock->get_result();
            
            if ($stock_result->num_rows > 0) {
                $stock_data = $stock_result->fetch_assoc();
                $available_stock = intval($stock_data['stock_quantity']);
                
                if ($quantity > $available_stock) {
                    $error_message = "Sorry, only {$available_stock} items are available in stock.";
                } else {
                    $_SESSION['cart'][$cart_key]['quantity'] = $quantity;
                    $success_message = "Cart updated!";
                }
            } else {
                $error_message = "Product no longer available.";
                unset($_SESSION['cart'][$cart_key]);
            }
            $stmt_stock->close();
        } else {
            unset($_SESSION['cart'][$cart_key]);
            $success_message = "Item removed from cart!";
        }
    }
}

// Handle apply promo code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_promo') {
    $promo_code = trim($_POST['promo_code'] ?? '');
    if ($promo_code === '') {
        $error_message = "Please enter a promo code.";
    } elseif (empty($_SESSION['cart'])) {
        $error_message = "Your cart is empty. Add items before applying a promo.";
    } else {
        $stmt = $connection->prepare("SELECT * FROM promo_codes WHERE code = ? AND status = 'active' AND valid_from <= NOW() AND valid_until >= NOW() LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $promo_code);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $promo = $res->fetch_assoc()) {
                // Check usage limits
                if (!is_null($promo['usage_limit']) && intval($promo['used_count']) >= intval($promo['usage_limit'])) {
                    $error_message = "This promo code has reached its usage limit.";
                } else {
                    // Check per-customer usage (simplified for promo_codes table)
                    $stmt2 = $connection->prepare("SELECT COUNT(*) AS cnt FROM promo_code_usage pcu WHERE pcu.promo_code_id = ? AND pcu.customer_id = ?");
                    $pid = intval($promo['id']);
                    $stmt2->bind_param('ii', $pid, $customer_id);
                    $stmt2->execute();
                    $r2 = $stmt2->get_result()->fetch_assoc();
                    $stmt2->close();
                    if (intval($r2['cnt']) >= 1) { // Assuming 1 use per customer for promo_codes
                        $error_message = "You have already used this promo code.";
                    }
                }

                if (!isset($error_message)) {
                    // Compute eligible subtotal and discount preview
                    [$eligible_subtotal, $discount_preview] = compute_promo_discount($connection, $_SESSION['cart'], $promo);
                    $min_order_amount = floatval($promo['min_order_amount'] ?? 0);
                    if ($eligible_subtotal < $min_order_amount) {
                        $error_message = "Promo requires a minimum eligible spend of ₱" . number_format($min_order_amount, 2) . ".";
                    } elseif ($discount_preview <= 0) {
                        $error_message = "This promo doesn't apply to your current cart.";
                    } else {
                        // Store minimal promo info in session
                        $_SESSION['applied_promo'] = [
                            'id' => intval($promo['id']),
                            'code' => $promo['code'],
                            'name' => $promo['description'],
                            'discount_type' => $promo['discount_type'],
                            'discount_value' => $promo['discount_value'],
                            'max_discount_amount' => $promo['max_discount_amount'],
                            'min_order_amount' => $promo['min_order_amount'],
                            'preview_discount' => $discount_preview
                        ];
                        $success_message = "Promo '" . htmlspecialchars($promo['code']) . "' applied.";
                    }
                }
            } else {
                $error_message = "Invalid or expired promo code.";
            }
            $stmt->close();
        } else {
            $error_message = "Failed to validate promo code. Please try again.";
        }
    }
}

// Handle remove promo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_promo') {
    $_SESSION['applied_promo'] = null;
    $success_message = "Promo removed.";
}

// Handle place order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    if (!empty($_SESSION['cart'])) {
        // Calculate cart totals first
        $cart_subtotal = 0;
        foreach ($_SESSION['cart'] as $item) {
            $cart_subtotal += $item['price'] * $item['quantity'];
        }

        // Promo discount recalculation and validation
        $discount_amount = 0.0;
        $applied_promo = $_SESSION['applied_promo'] ?? null;
        $promotion_id_for_order = null;
        if (!empty($applied_promo) && !empty($applied_promo['code'])) {
            // Revalidate promo from DB with same code
            $stmt = $connection->prepare("SELECT * FROM promo_codes WHERE id = ? AND code = ? AND status = 'active' AND valid_from <= NOW() AND valid_until >= NOW() LIMIT 1");
            $promo_id_int = intval($applied_promo['id']);
            $promo_code_str = $applied_promo['code'];
            if ($stmt) {
                $stmt->bind_param('is', $promo_id_int, $promo_code_str);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $promo_db = $res->fetch_assoc()) {
                    // Check global and per-customer usage
                    if (!is_null($promo_db['usage_limit']) && intval($promo_db['used_count']) >= intval($promo_db['usage_limit'])) {
                        $error_message = "This promo code has reached its usage limit.";
                    } else {
                        $stmt2 = $connection->prepare("SELECT COUNT(*) AS cnt FROM promo_code_usage pcu WHERE pcu.promo_code_id = ? AND pcu.customer_id = ?");
                        $pid = intval($promo_db['id']);
                        $stmt2->bind_param('ii', $pid, $customer_id);
                        $stmt2->execute();
                        $r2 = $stmt2->get_result()->fetch_assoc();
                        $stmt2->close();
                        if (intval($r2['cnt']) >= 1) {
                            $error_message = "You have already used this promo code.";
                        }
                    }

                    if (!isset($error_message)) {
                        [$eligible_subtotal, $discount_calc] = compute_promo_discount($connection, $_SESSION['cart'], $promo_db);
                        $min_order_amount = floatval($promo_db['min_order_amount'] ?? 0);
                        if ($eligible_subtotal < $min_order_amount || $discount_calc <= 0) {
                            // Do not apply
                            $discount_amount = 0.0;
                        } else {
                            $discount_amount = $discount_calc;
                            $promotion_id_for_order = intval($promo_db['id']);
                        }
                    }
                }
                $stmt->close();
            }
        }

        // Compute tax using settings rate on discounted subtotal
        $taxable_amount = max($cart_subtotal - $discount_amount, 0);
        $cart_tax = $taxable_amount * ($tax_rate_percent / 100.0);
        $cart_total = $taxable_amount + $cart_tax;
        
        // Get payment method
        $payment_method = $_POST['payment_method'] ?? '';
        $use_wallet = ($payment_method === 'wallet');
        
        // Convert 'wallet' to 'digital_wallet' for database storage
        if ($payment_method === 'wallet') {
            $payment_method = 'digital_wallet';
        }
        
        // Validate payment method
        if (empty($payment_method)) {
            $error_message = "Please select a payment method before placing your order.";
        } elseif ($use_wallet && $wallet_balance < $cart_total) {
            $error_message = "Insufficient wallet balance. You need ₱" . number_format($cart_total - $wallet_balance, 2) . " more to complete this order.";
        } else {
            try {
                $connection->begin_transaction();
                
                // Validate stock availability for all items in cart
                $stock_validation_passed = true;
                $stock_errors = [];
                
                foreach ($_SESSION['cart'] as $cart_key => $item) {
                    $product_id = intval(explode('_', $cart_key)[0]);
                    $stmt_stock = $connection->prepare("SELECT name, stock_quantity FROM products WHERE id = ? AND status = 'active'");
                    $stmt_stock->bind_param("i", $product_id);
                    $stmt_stock->execute();
                    $stock_result = $stmt_stock->get_result();
                    
                    if ($stock_result->num_rows > 0) {
                        $product_data = $stock_result->fetch_assoc();
                        $available_stock = intval($product_data['stock_quantity']);
                        
                        if ($available_stock < $item['quantity']) {
                            $stock_validation_passed = false;
                            $stock_errors[] = "{$product_data['name']} ({$item['size']}): Only {$available_stock} available (requested: {$item['quantity']})";
                        }
                    } else {
                        $stock_validation_passed = false;
                        $stock_errors[] = "{$item['name']} ({$item['size']}): Product no longer available";
                    }
                    $stmt_stock->close();
                }
                
                if (!$stock_validation_passed) {
                    $connection->rollback();
                    $error_message = "Order cannot be placed due to stock issues:<br>" . implode("<br>", $stock_errors);
                } else {
                    // Use already calculated totals
                    $subtotal = $cart_subtotal;
                    $tax_amount = $cart_tax;
                    $final_amount = $cart_total;
                    
                    // Generate order number
                    $order_number = 'SB' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    
                    // Insert order with discount and payment method
                    $stmt_order = $connection->prepare("INSERT INTO orders (customer_id, order_number, total_amount, tax_amount, discount_amount, final_amount, status, payment_status, payment_method, wallet_payment, order_type) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, 'takeaway')");
                    if (!$stmt_order) {
                        throw new Exception("Prepare failed: " . $connection->error);
                    }
                    $stmt_order->bind_param("isddddsi", $customer_id, $order_number, $subtotal, $tax_amount, $discount_amount, $final_amount, $payment_method, $use_wallet);
                    $stmt_order->execute();
                    if ($stmt_order->affected_rows === 0) {
                        throw new Exception("Failed to insert order.");
                    }
                    $order_id = $connection->insert_id;
                    $stmt_order->close();
                    
                    // Insert order items and update stock
                    $stmt_item = $connection->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price, selected_size) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt_update_stock = $connection->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                    
                    foreach ($_SESSION['cart'] as $cart_key => $item) {
                        $product_id = intval(explode('_', $cart_key)[0]);
                        $total_price = $item['price'] * $item['quantity'];
                        $stmt_item->bind_param("iisidid", $order_id, $product_id, $item['name'], $item['quantity'], $item['price'], $total_price, $item['size']);
                        $stmt_item->execute();
                        
                        // Update stock quantity
                        $stmt_update_stock->bind_param("ii", $item['quantity'], $product_id);
                        $stmt_update_stock->execute();
                    }
                    $stmt_item->close();
                    $stmt_update_stock->close();

                    // Persist promotion usage if applied
                    if (!is_null($promotion_id_for_order) && $discount_amount > 0) {
                        // Record promo code usage
                        $stmt_usage = $connection->prepare("INSERT INTO promo_code_usage (promo_code_id, customer_id, order_id, discount_amount, original_amount, final_amount) VALUES (?, ?, ?, ?, ?, ?)");
                        $original_amount = $cart_subtotal;
                        $final_amount = $cart_total;
                        $stmt_usage->bind_param('iiiddd', $promotion_id_for_order, $customer_id, $order_id, $discount_amount, $original_amount, $final_amount);
                        $stmt_usage->execute();
                        $stmt_usage->close();

                        // Increment usage_count safely (this will be handled by the trigger)
                        $stmt_inc = $connection->prepare("UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?");
                        $stmt_inc->bind_param('i', $promotion_id_for_order);
                        $stmt_inc->execute();
                        $stmt_inc->close();
                    }
                    
                    // Award loyalty points (earn on final_amount before wallet payment)
                    if ($loyalty_rate > 0) {
                        $points_earned = (int) floor($final_amount * $loyalty_rate);
                        if ($points_earned > 0) {
                            try {
                                $stmt_lp = $connection->prepare("INSERT INTO loyalty_points (customer_id, points, transaction_type, order_id, description) VALUES (?, ?, 'earned', ?, 'Points earned for order')");
                                $stmt_lp->bind_param('iii', $customer_id, $points_earned, $order_id);
                                $stmt_lp->execute();
                                $stmt_lp->close();
                            } catch (Exception $e) { /* ignore */ }
                        }
                    }

                    // Process wallet payment if using wallet
                    if ($use_wallet) {
                        // Get wallet ID
                        $stmt_wallet_id = $connection->prepare("SELECT id, balance FROM digital_wallet WHERE customer_id = ?");
                        $stmt_wallet_id->bind_param("i", $customer_id);
                        $stmt_wallet_id->execute();
                        $wallet_result = $stmt_wallet_id->get_result();
                        $wallet_data = $wallet_result->fetch_assoc();
                        $wallet_id = $wallet_data['id'];
                        $current_balance = $wallet_data['balance'];
                        $stmt_wallet_id->close();
                        
                        // Update wallet balance
                        $new_balance = $current_balance - $final_amount;
                        $stmt_wallet_update = $connection->prepare("UPDATE digital_wallet SET balance = ? WHERE id = ?");
                        $stmt_wallet_update->bind_param("di", $new_balance, $wallet_id);
                        $stmt_wallet_update->execute();
                        
                        // Record wallet transaction (if table exists)
                        try {
                            $stmt_wallet_transaction = $connection->prepare("INSERT INTO wallet_transactions (customer_id, wallet_id, transaction_type, amount, balance_before, balance_after, description, order_id) VALUES (?, ?, 'payment', ?, ?, ?, ?, ?)");
                            $description = "Payment for Order #" . $order_number;
                            $stmt_wallet_transaction->bind_param("iidddsi", $customer_id, $wallet_id, $final_amount, $current_balance, $new_balance, $description, $order_id);
                            $stmt_wallet_transaction->execute();
                            $stmt_wallet_transaction->close();
                        } catch (Exception $e) {
                            // Table might not exist yet, continue without recording transaction
                            error_log("Wallet transactions table not found: " . $e->getMessage());
                        }
                        $stmt_wallet_update->close();
                    }
                    
                    $connection->commit();
                    
                    // Clear cart
                    $_SESSION['cart'] = [];
                    $_SESSION['applied_promo'] = null;
                    
                    $payment_text = $use_wallet ? " (Paid with Digital Wallet)" : " (Payment Method: " . ucfirst(str_replace('_', ' ', $payment_method)) . ")";
                    // Redirect straight to receipt so the customer sees it immediately
                    header('Location: customer_place_order.php?receipt=1&order_id=' . intval($order_id));
                    exit();
                }
                
            } catch (Exception $e) {
                $connection->rollback();
                error_log("Order placement error: " . $e->getMessage());
                $error_message = "Error placing order. Please try again. Error: " . $e->getMessage();
            }
        }
    } else {
        $error_message = "Your cart is empty!";
    }
}

// Fetch active products with size information
$products_query = "
    SELECT p.id, p.name, p.description, p.price, p.image_url, p.stock_quantity, p.min_stock_level, p.category_id,
           GROUP_CONCAT(ps.size_name ORDER BY 
               CASE ps.size_name 
                   WHEN 'Small' THEN 1 
                   WHEN 'Medium' THEN 2 
                   WHEN 'Large' THEN 3 
                   WHEN 'XL' THEN 4 
                   ELSE 5 
               END
           ) as available_sizes
    FROM products p 
    LEFT JOIN product_sizes ps ON p.id = ps.product_id AND ps.is_available = 1
    WHERE p.status = 'active' 
    GROUP BY p.id, p.name, p.description, p.price, p.image_url, p.stock_quantity, p.min_stock_level, p.category_id
    ORDER BY p.name ASC
";
$products_result = mysqli_query($connection, $products_query);

if (!$products_result) {
    die("Database query failed: " . mysqli_error($connection));
}

$products = mysqli_fetch_all($products_result, MYSQLI_ASSOC);

// Calculate cart count for display
$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_count += $item['quantity'];
}

// Calculate cart totals for display (if not already calculated)
if (!isset($cart_subtotal)) {
    $cart_subtotal = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_subtotal += $item['price'] * $item['quantity'];
    }
    // Apply promo for preview if present
    $preview_discount = 0.0;
    if (!empty($_SESSION['applied_promo'])) {
        [$eligible_subtotal_preview, $discount_preview_calc] = compute_promo_discount($connection, $_SESSION['cart'], $_SESSION['applied_promo']);
        $min_needed = floatval($_SESSION['applied_promo']['min_order_amount'] ?? 0);
        if ($eligible_subtotal_preview >= $min_needed) {
            $preview_discount = $discount_preview_calc;
        }
    }
    $taxable_amount_preview = max($cart_subtotal - $preview_discount, 0);
    $cart_tax = $taxable_amount_preview * ($tax_rate_percent / 100.0);
    $cart_total = $taxable_amount_preview + $cart_tax;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - Starbucks Customer Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            border-bottom: 2px solid #00704a;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 {
            color: #00704a;
            font-size: 2rem;
            margin-bottom: 0.2rem;
        }

        .logo p {
            color: #666;
            font-size: 0.9rem;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .customer-info span {
            color: #333;
            font-weight: 500;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .nav-menu {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid #e0e0e0;
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: center;
            gap: 2rem;
        }

        .nav-item {
            background: linear-gradient(135deg, #00704a, #28a745);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(0, 112, 74, 0.3);
        }

        .nav-item:hover {
            background: linear-gradient(135deg, #005a3c, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 112, 74, 0.4);
        }

        .nav-item.active {
            background: linear-gradient(135deg, #ffc107, #ffcd39);
            color: #212529;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }

        .nav-item.active:hover {
            background: linear-gradient(135deg, #e0a800, #ffb300);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .success-message, .error-message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .warning-message {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .section {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .section h2 {
            color: #00704a;
            margin-bottom: 1rem;
            font-size: 1.5rem;
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
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin-bottom: 30px;
            padding: 1rem 0;
        }
        .product-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            flex-direction: column;
            border: 2px solid transparent;
            position: relative;
            backdrop-filter: blur(10px);
        }
        .product-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,112,74,0.15);
            border-color: #00704a;
        }
        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00704a, #28a745, #20c997);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .product-card:hover::before {
            opacity: 1;
        }
        .product-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-bottom: 2px solid #e9ecef;
            transition: transform 0.3s ease;
        }
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        .product-content {
            padding: 2rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .product-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #00704a;
            margin-bottom: 0.8rem;
            line-height: 1.3;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .product-description {
            font-size: 0.95rem;
            color: #666;
            line-height: 1.5;
            margin-bottom: 1.2rem;
            flex-grow: 1;
            font-style: italic;
        }
        .product-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: #00704a;
            margin-bottom: 1.5rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
            background: linear-gradient(135deg, #00704a, #28a745);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .add-to-cart-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-top: auto;
        }
        
        .size-selection {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .size-selection label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .size-options {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .size-option {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            min-width: 60px;
            justify-content: center;
        }
        
        .size-option:hover {
            border-color: #00704a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 112, 74, 0.2);
        }
        
        .size-option input[type="radio"] {
            display: none;
        }
        
        .size-option input[type="radio"]:checked + .size-option-text {
            color: #00704a;
            font-weight: bold;
        }
        
        .size-option.selected {
            border-color: #00704a;
            background: linear-gradient(135deg, #e8f5e8, #f0fff5);
            box-shadow: 0 4px 12px rgba(0, 112, 74, 0.3);
        }
        
        .size-option-text {
            font-size: 0.85rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-row {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .product-price {
            transition: all 0.3s ease;
        }
        
        .product-price.updating {
            color: #00704a;
            font-weight: bold;
            transform: scale(1.05);
        }
        
        .size-option-text {
            position: relative;
        }
        
        .size-option-text::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #00704a;
            transition: width 0.3s ease;
        }
        
        .size-option.selected .size-option-text::after {
            width: 100%;
        }
        
        .price-indicator {
            font-size: 0.75rem;
            color: #00704a;
            font-weight: 600;
            margin-top: 2px;
        }
        
        .price-increase {
            color: #dc3545;
        }
        
        .price-decrease {
            color: #28a745;
        }
        .quantity-input {
            width: 70px;
            padding: 8px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .quantity-input:focus {
            outline: none;
            border-color: #00704a;
            box-shadow: 0 0 0 3px rgba(0, 112, 74, 0.1);
        }
        .add-btn {
            background: linear-gradient(135deg, #00704a, #28a745);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(0, 112, 74, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .add-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #005a3c, #1e7e34);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 112, 74, 0.4);
        }
        .add-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Stock display styles */
        .stock-info {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: bold;
            text-align: center;
        }
        .stock-high {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .stock-low {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .stock-out {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Cart sidebar */
        .cart-sidebar {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2rem;
            height: fit-content;
            position: sticky;
            top: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #00704a;
        }
        .cart-count {
            background: linear-gradient(135deg, #00704a, #28a745);
            color: white;
            border-radius: 50%;
            padding: 8px 12px;
            font-size: 0.9rem;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0, 112, 74, 0.3);
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }
        
        .cart-count.updated {
            animation: bounce 0.6s ease;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 4px 15px rgba(0, 112, 74, 0.3); }
            50% { box-shadow: 0 4px 20px rgba(0, 112, 74, 0.5); }
            100% { box-shadow: 0 4px 15px rgba(0, 112, 74, 0.3); }
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }
        .cart-item:hover {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 0 -15px;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item-details h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 1rem;
            font-weight: 600;
        }
        .cart-item-size {
            color: #00704a;
            font-size: 0.8rem;
            font-weight: 600;
            background: #e8f5e8;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 3px;
        }
        .cart-item-price {
            color: #666;
            font-size: 0.9rem;
        }
        
        .cart-item-price .size-adjusted {
            color: #00704a;
            font-weight: 600;
        }
        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cart-quantity {
            width: 60px;
            padding: 5px;
            border: 2px solid #ddd;
            border-radius: 6px;
            text-align: center;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .cart-quantity:focus {
            outline: none;
            border-color: #00704a;
            box-shadow: 0 0 0 3px rgba(0, 112, 74, 0.1);
        }
        .remove-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
        }
        .remove-btn:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.4);
        }
        .cart-summary {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #00704a;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 0.5rem 0;
            font-size: 1rem;
        }
        .summary-total {
            font-weight: bold;
            font-size: 1.2rem;
            color: #00704a;
            border-top: 2px solid #00704a;
            padding-top: 12px;
            margin-top: 8px;
        }
        .place-order-btn {
            width: 100%;
            background: linear-gradient(135deg, #00704a, #28a745);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 112, 74, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .place-order-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #005a3c, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 112, 74, 0.4);
        }
        .place-order-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #00704a;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Cart item animations */
        .cart-item-enter {
            animation: cartItemEnter 0.3s ease-out;
        }
        
        .cart-item-exit {
            animation: cartItemExit 0.3s ease-out;
        }
        
        @keyframes cartItemEnter {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes cartItemExit {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(20px);
            }
        }
        
        /* Payment Method Styles */
        .payment-section {
            margin: 20px 0;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            border-radius: 12px;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .payment-section h4 {
            color: #00704a;
            margin: 0 0 15px 0;
            font-size: 1.1rem;
            font-weight: bold;
        }
        
        .payment-options {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }
        
        .payment-option {
            display: flex;
            align-items: center;
            padding: 15px;
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border: 2px solid #e9ecef;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .payment-option:hover {
            border-color: #00704a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 112, 74, 0.2);
        }
        
        .payment-option input[type="radio"] {
            display: none;
        }
        
        .payment-option input[type="radio"]:checked + .payment-fallback + .payment-text {
            color: #00704a;
            font-weight: bold;
        }
        
        .payment-option.selected {
            border-color: #00704a;
            background: linear-gradient(135deg, #e8f5e8, #f0fff5);
            box-shadow: 0 4px 12px rgba(0, 112, 74, 0.3);
        }
        
        .payment-fallback {
            font-size: 1.2rem;
            margin-right: 10px;
            display: inline-block;
            vertical-align: middle;
        }
        
        .payment-text {
            font-size: 0.95rem;
            color: #333;
            font-weight: 500;
        }
        
        /* Payment method selection indicator */
        .payment-option::after {
            content: '';
            position: absolute;
            top: 10px;
            right: 10px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid #e9ecef;
            background-color: white;
            transition: all 0.3s ease;
        }
        
        .payment-option:has(input[type="radio"]:checked)::after {
            background-color: #00704a;
            border-color: #00704a;
        }
        
        /* Messages */
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        }
        .success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
            box-shadow: 0 2px 10px rgba(21, 87, 36, 0.1);
        }
        .error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
            box-shadow: 0 2px 10px rgba(114, 28, 36, 0.1);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .empty-cart {
            text-align: center;
            color: #666;
            padding: 3rem;
        }
        .empty-cart h3 {
            color: #00704a;
            margin-bottom: 1rem;
        }
        .empty-cart p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        .browse-products-btn {
            background: linear-gradient(135deg, #00704a, #28a745);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0, 112, 74, 0.3);
        }
        .browse-products-btn:hover {
            background: linear-gradient(135deg, #005a3c, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 112, 74, 0.4);
        }
        
        @media (max-width: 768px) {
            .nav-content {
                flex-direction: column;
                gap: 1rem;
                align-items: center;
            }

            .nav-item {
                width: 100%;
                justify-content: center;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .main-content {
                grid-template-columns: 1fr;
            }
            .cart-sidebar {
                position: static;
            }
            .payment-options {
                grid-template-columns: 1fr;
            }
            .payment-option {
                padding: 12px;
            }
            .payment-fallback {
                font-size: 1.2rem;
            }
            .payment-text {
                font-size: 0.9rem;
            }
            .container {
                padding: 1rem;
            }
            .section {
                padding: 1.5rem;
            }
            .product-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            .product-card {
                margin: 0 auto;
                max-width: 400px;
            }
            .nav-content {
                flex-direction: column;
                gap: 0.5rem;
            }
            .nav-btn {
                width: 100%;
                justify-content: center;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>☕ Starbucks</h1>
                <p>Customer Portal</p>
            </div>
            <div class="customer-info">
                <span>Welcome, <?php echo $customer_username; ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <!-- Navigation Menu -->
    <nav class="nav-menu">
        <div class="nav-content">
            <a href="customer_dashboard.php" class="nav-item">
                🏠 Dashboard
            </a>
            <a href="customer_place_order.php" class="nav-item active">
                🛒 Place Order
            </a>
            <a href="customer_digital_wallet.php" class="nav-item">
                💳 Digital Wallet
            </a>
            <a href="customer_product.php" class="nav-item">
                📋 Browse Products
            </a>
            <a href="customer_profile.php" class="nav-item">
                👤 Profile
            </a>
        </div>
    </nav>

    <div class="container">
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
        <div class="success-message">
            ✅ <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
        <div class="error-message">
            ❌ <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        
        <div class="main-content">
            <!-- Products Section -->
            <div class="section">
                <h2>🍽️ Available Products</h2>
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
                            <?php 
                                $stock_quantity = intval($product['stock_quantity']);
                                $min_stock_level = intval($product['min_stock_level']);
                                
                                // Determine stock status
                                $stock_class = 'stock-high';
                                $stock_text = "In Stock ({$stock_quantity} available)";
                                $is_available = true;
                                
                                if ($stock_quantity <= 0) {
                                    $stock_class = 'stock-out';
                                    $stock_text = "Out of Stock";
                                    $is_available = false;
                                } elseif ($stock_quantity <= $min_stock_level) {
                                    $stock_class = 'stock-low';
                                    $stock_text = "Low Stock ({$stock_quantity} remaining)";
                                }
                            ?>
                            <div class="product-card" data-product-id="<?php echo $product['id']; ?>" data-base-price="<?php echo $product['price']; ?>">
                                <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image" />
                                <div class="product-content">
                                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div class="product-description"><?php echo htmlspecialchars($product['description'] ?? 'No description available.'); ?></div>
                                    <div class="product-price" id="price-<?php echo $product['id']; ?>">
                                        ₱<?php echo htmlspecialchars(number_format($product['price'], 2)); ?>
                                    </div>
                                    
                                    <!-- Stock Information -->
                                    <div class="stock-info <?php echo $stock_class; ?>">
                                        <?php echo $stock_text; ?>
                                    </div>
                                    
                                    <form method="POST" class="add-to-cart-form">
                                        <input type="hidden" name="action" value="add_to_cart">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        
                                        <?php 
                                        // Check if product has size options (beverages typically do)
                                        $has_sizes = in_array($product['category_id'], [1, 2, 3, 4, 5, 6, 7]); // Beverage categories
                                        $available_sizes = $has_sizes ? explode(',', $product['available_sizes']) : ['One Size'];
                                        ?>
                                        
                                        <?php if ($has_sizes): ?>
                                        <div class="size-selection">
                                            <label>Size:</label>
                                            <div class="size-options">
                                                <?php 
                                                // Get size pricing data for this product
                                                $size_pricing_query = "SELECT size_name, size_price_modifier FROM product_sizes WHERE product_id = ? AND is_available = 1 ORDER BY 
                                                    CASE size_name 
                                                        WHEN 'Small' THEN 1 
                                                        WHEN 'Medium' THEN 2 
                                                        WHEN 'Large' THEN 3 
                                                        WHEN 'XL' THEN 4 
                                                        ELSE 5 
                                                    END";
                                                $stmt_sizes = $connection->prepare($size_pricing_query);
                                                $stmt_sizes->bind_param("i", $product['id']);
                                                $stmt_sizes->execute();
                                                $size_result = $stmt_sizes->get_result();
                                                $size_pricing = [];
                                                while ($size_row = $size_result->fetch_assoc()) {
                                                    $size_pricing[$size_row['size_name']] = $size_row['size_price_modifier'];
                                                }
                                                $stmt_sizes->close();
                                                ?>
                                                <?php foreach ($available_sizes as $size): ?>
                                                    <?php 
                                                    $size = trim($size); 
                                                    $size_modifier = isset($size_pricing[$size]) ? $size_pricing[$size] : 0;
                                                    $final_price = $product['price'] + $size_modifier;
                                                    ?>
                                                    <label class="size-option" data-size-modifier="<?php echo $size_modifier; ?>" data-final-price="<?php echo $final_price; ?>">
                                                        <input type="radio" name="size" value="<?php echo htmlspecialchars($size); ?>" 
                                                               <?php echo $size === 'Medium' ? 'checked' : ''; ?> required>
                                                        <span class="size-option-text"><?php echo htmlspecialchars($size); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                            <input type="hidden" name="size" value="One Size">
                                        <?php endif; ?>
                                        
                                        <div class="form-row">
                                            <input type="number" name="quantity" min="1" max="<?php echo $stock_quantity; ?>" value="1" class="quantity-input" <?php echo $is_available ? '' : 'disabled'; ?> required>
                                            <button type="submit" class="add-btn" <?php echo $is_available ? '' : 'disabled'; ?>>
                                                <?php echo $is_available ? 'Add to Cart' : 'Out of Stock'; ?>
                                            </button>
                                        </div>
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
            <div class="section">
                <div class="cart-header">
                    <h2>🛒 Your Cart</h2>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </div>
                
                <?php if (empty($_SESSION['cart'])): ?>
                    <div class="empty-cart">
                        <h3>🛒 Your Cart is Empty</h3>
                        <p>Add some delicious items to your cart to get started!</p>
                        <a href="customer_view_products.php" class="browse-products-btn">Browse Products</a>
                    </div>
                <?php else: ?>
                    <div class="cart-items">
                        <?php foreach ($_SESSION['cart'] as $cart_key => $item): ?>
                            <div class="cart-item">
                                <div class="cart-item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <div class="cart-item-size"><?php echo htmlspecialchars($item['size']); ?></div>
                                    <div class="cart-item-price">
                                        <span class="size-adjusted">₱<?php echo number_format($item['price'], 2); ?></span> each
                                    </div>
                                </div>
                                <div class="cart-item-controls">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_cart">
                                        <input type="hidden" name="cart_key" value="<?php echo htmlspecialchars($cart_key); ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="0" class="cart-quantity" 
                                               onchange="this.form.submit()">
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove_from_cart">
                                        <input type="hidden" name="cart_key" value="<?php echo htmlspecialchars($cart_key); ?>">
                                        <button type="submit" class="remove-btn">Remove</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-summary">
                        <?php
                        // Simple "Buy again" based on past completed orders for this customer
                        $buy_again = [];
                        $stmt_ba = $connection->prepare("SELECT oi.product_id, oi.product_name, SUM(oi.quantity) AS qty FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.customer_id = ? AND o.status = 'completed' GROUP BY oi.product_id, oi.product_name ORDER BY qty DESC LIMIT 5");
                        if ($stmt_ba) {
                            $stmt_ba->bind_param('i', $customer_id);
                            $stmt_ba->execute();
                            $buy_again = $stmt_ba->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
                            $stmt_ba->close();
                        }
                        ?>
                        <?php if (!empty($buy_again)): ?>
                        <div class="warning-message" style="margin-top:0;margin-bottom:16px;">
                            <div style="margin-bottom:6px;">Buy again</div>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                <?php foreach ($buy_again as $ba): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="add_to_cart">
                                        <input type="hidden" name="product_id" value="<?php echo intval($ba['product_id']); ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <input type="hidden" name="size" value="Medium">
                                        <button type="submit" class="add-btn" style="padding:6px 10px;font-size:0.85rem;">+ <?php echo htmlspecialchars($ba['product_name']); ?></button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <!-- Promo code apply/remove -->
                        <form method="POST" class="mb-3" style="display: <?php echo empty($_SESSION['cart']) ? 'none' : 'block'; ?>;">
                            <input type="hidden" name="action" value="<?php echo empty($_SESSION['applied_promo']) ? 'apply_promo' : 'remove_promo'; ?>">
                            <?php if (empty($_SESSION['applied_promo'])): ?>
                                <div class="form-row" style="margin-bottom:10px;">
                                    <input type="text" name="promo_code" placeholder="Enter promo code" class="quantity-input" style="flex:1;width:auto;min-width:200px;" 
                                           value="<?php echo htmlspecialchars($_POST['promo_code'] ?? ''); ?>" />
                                    <button type="submit" class="add-btn">Apply</button>
                                </div>
                                <div style="font-size:0.85rem;color:#666;margin-top:5px;">
                                    💡 Try codes like: WELCOME20, SAVE50, BULK10, FLASH30
                                </div>
                            <?php else: ?>
                                <div class="warning-message" style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                                    <span>Promo applied: <strong><?php echo htmlspecialchars($_SESSION['applied_promo']['code']); ?></strong> — <?php echo htmlspecialchars($_SESSION['applied_promo']['name']); ?></span>
                                    <button type="submit" class="remove-btn">Remove</button>
                                </div>
                            <?php endif; ?>
                        </form>

                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>₱<?php echo number_format($cart_subtotal, 2); ?></span>
                        </div>
                        <?php if (!empty($_SESSION['applied_promo']) && $preview_discount > 0): ?>
                        <div class="summary-row" style="color:#28a745;font-weight:600;">
                            <span>Promo (<?php echo htmlspecialchars($_SESSION['applied_promo']['code']); ?>):</span>
                            <span>-₱<?php echo number_format($preview_discount, 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="summary-row">
                            <span>Tax (<?php echo number_format($tax_rate_percent, 2); ?>%):</span>
                            <span>₱<?php echo number_format($cart_tax, 2); ?></span>
                        </div>
                        <div class="summary-row summary-total">
                            <span>Total:</span>
                            <span>₱<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                        
                        <!-- Payment Method Selection -->
                        <form method="POST" id="place-order-form">
                            <input type="hidden" name="action" value="place_order">
                            
                            <div class="payment-section">
                                <h4>Payment Method</h4>
                                <div class="payment-options">
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="cash" required>
                                        <span class="payment-fallback">💵</span>
                                        <span class="payment-text">Cash</span>
                                    </label>
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="card" required>
                                        <span class="payment-fallback">💳</span>
                                        <span class="payment-text">Card</span>
                                    </label>
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="wallet" required>
                                        <span class="payment-fallback">📱</span>
                                        <span class="payment-text">Digital Wallet (₱<?php echo number_format($wallet_balance, 2); ?>)</span>
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="place-order-btn" id="place-order-btn" disabled>Place Order</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide success/error messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.success-message, .error-message, .warning-message');
            messages.forEach(function(message) {
                message.style.transition = 'opacity 0.5s, transform 0.5s';
                message.style.opacity = '0';
                message.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    message.remove();
                }, 500);
            });
        }, 5000);

        // Add smooth scroll to top when adding items to cart
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Add item to cart with animation
        function addToCartWithAnimation(form) {
            const productCard = form.closest('.product-card');
            const productName = productCard.querySelector('.product-name').textContent;
            
            // Show loading state
            const submitBtn = form.querySelector('.add-btn');
            const originalText = submitBtn.textContent;
            submitBtn.innerHTML = '<span class="loading-spinner"></span>Adding...';
            submitBtn.disabled = true;
            
            // Animate cart counter
            animateCartCounter();
            
            // Scroll to top to show success message
            setTimeout(scrollToTop, 100);
        }

        // Animate cart counter
        function animateCartCounter() {
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.classList.add('updated');
                setTimeout(() => {
                    cartCount.classList.remove('updated');
                }, 600);
            }
        }

        // Enhanced cart item removal with animation
        function removeCartItemWithAnimation(button) {
            const cartItem = button.closest('.cart-item');
            cartItem.classList.add('cart-item-exit');
            
            // Animate cart counter
            animateCartCounter();
            
            setTimeout(function() {
                cartItem.remove();
            }, 300);
        }

        // Payment method selection functionality
        document.addEventListener('DOMContentLoaded', function() {
            const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
            const placeOrderBtn = document.getElementById('place-order-btn');
            const placeOrderForm = document.getElementById('place-order-form');
            
            // Enable/disable place order button based on payment method selection
            function updatePlaceOrderButton() {
                const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
                if (selectedPayment) {
                    placeOrderBtn.disabled = false;
                    const paymentText = selectedPayment.nextElementSibling.nextElementSibling.textContent;
                    placeOrderBtn.innerHTML = `Place Order (${paymentText.split(' ')[0]})`;
                } else {
                    placeOrderBtn.disabled = true;
                    placeOrderBtn.textContent = 'Select Payment Method';
                }
            }
            
            // Add event listeners to payment options
            paymentOptions.forEach(option => {
                option.addEventListener('change', function() {
                    updatePlaceOrderButton();
                    
                    // Add visual feedback
                    paymentOptions.forEach(opt => {
                        const label = opt.closest('.payment-option');
                        label.classList.remove('selected');
                    });
                    
                    if (this.checked) {
                        this.closest('.payment-option').classList.add('selected');
                    }
                });
            });
            
            // Handle form submission
            if (placeOrderForm) {
                placeOrderForm.addEventListener('submit', function(e) {
                    const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
                    if (!selectedPayment) {
                        e.preventDefault();
                        alert('Please select a payment method before placing your order.');
                        return false;
                    }
                    
                    // Add confirmation dialog
                    const totalAmount = document.querySelector('.summary-total span:last-child').textContent;
                    const paymentMethod = selectedPayment.nextElementSibling.nextElementSibling.textContent;
                    
                    if (!confirm(`Confirm your order?\n\nPayment Method: ${paymentMethod}\nTotal Amount: ${totalAmount}\n\nClick OK to place your order.`)) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Add loading state
                    placeOrderBtn.disabled = true;
                    placeOrderBtn.textContent = 'Processing...';
                });
            }
            
            // Add click handlers for payment options
            document.querySelectorAll('.payment-option').forEach(option => {
                option.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                });
            });
            
            // Initialize button state
            updatePlaceOrderButton();
        });
        

        // Add visual feedback for form interactions
        document.querySelectorAll('select, input, button').forEach(function(element) {
            element.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
                this.style.transition = 'transform 0.2s';
            });
            
            element.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Smooth scroll for better UX
        document.documentElement.style.scrollBehavior = 'smooth';

        // Add hover effects to cart items
        document.querySelectorAll('.cart-item').forEach(function(item) {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });

        // Add loading animation for buttons
        document.querySelectorAll('button').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!this.disabled) {
                    this.style.opacity = '0.7';
                    this.style.transform = 'scale(0.98)';
                    
                    setTimeout(() => {
                        this.style.opacity = '1';
                        this.style.transform = 'scale(1)';
                    }, 200);
                }
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + D to go to Dashboard
            if (e.altKey && e.key === 'd') {
                e.preventDefault();
                window.location.href = 'customer_dashboard.php';
            }
            
            // Alt + P to go to Browse Products
            if (e.altKey && e.key === 'p') {
                e.preventDefault();
                window.location.href = 'customer_view_products.php';
            }
            
            // Alt + H to go to Order History
            if (e.altKey && e.key === 'h') {
                e.preventDefault();
                window.location.href = 'customer_order_history.php';
            }
            
            // Alt + O to go to Profile
            if (e.altKey && e.key === 'o') {
                e.preventDefault();
                window.location.href = 'customer_profile.php';
            }
            
            // Alt + L to logout
            if (e.altKey && e.key === 'l') {
                e.preventDefault();
                if (confirm('Are you sure you want to logout?')) {
                    window.location.href = 'logout.php';
                }
            }
        });

        // Add tooltip functionality
        function addTooltips() {
            const tooltipElements = document.querySelectorAll('[title]');
            tooltipElements.forEach(function(element) {
                element.addEventListener('mouseenter', function() {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'tooltip';
                    tooltip.textContent = this.title;
                    tooltip.style.cssText = `
                        position: absolute;
                        background: rgba(0, 0, 0, 0.8);
                        color: white;
                        padding: 0.5rem;
                        border-radius: 4px;
                        font-size: 0.8rem;
                        z-index: 1000;
                        pointer-events: none;
                        white-space: nowrap;
                    `;
                    document.body.appendChild(tooltip);
                    
                    // Position tooltip
                    const rect = this.getBoundingClientRect();
                    tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
                    tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
                    
                    // Remove title to prevent default tooltip
                    this.dataset.originalTitle = this.title;
                    this.title = '';
                });
                
                element.addEventListener('mouseleave', function() {
                    const tooltip = document.querySelector('.tooltip');
                    if (tooltip) {
                        tooltip.remove();
                    }
                    
                    // Restore title
                    if (this.dataset.originalTitle) {
                        this.title = this.dataset.originalTitle;
                        delete this.dataset.originalTitle;
                    }
                });
            });
        }

        // Initialize tooltips
        addTooltips();

        // Add quantity input validation
        document.querySelectorAll('.cart-quantity, .quantity-input').forEach(function(input) {
            input.addEventListener('input', function() {
                if (this.value < 0) {
                    this.value = 0;
                }
            });
        });

        // Add confirmation for remove buttons
        document.querySelectorAll('.remove-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to remove this item from your cart?')) {
                    e.preventDefault();
                } else {
                    // Add animation before form submission
                    removeCartItemWithAnimation(this);
                }
            });
        });

        // Add visual feedback for quantity changes
        document.querySelectorAll('.cart-quantity').forEach(function(input) {
            input.addEventListener('change', function() {
                const row = this.closest('.cart-item');
                row.style.transition = 'all 0.3s ease';
                row.style.backgroundColor = '#e8f5e8';
                
                setTimeout(function() {
                    row.style.backgroundColor = '';
                }, 2000);
            });
        });

        // Add loading state to forms
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"], input[type="submit"]');
                const action = this.querySelector('input[name="action"]');
                
                if (submitBtn) {
                    submitBtn.style.opacity = '0.7';
                    submitBtn.style.pointerEvents = 'none';
                }
                
                // Special handling for add to cart forms
                if (action && action.value === 'add_to_cart') {
                    addToCartWithAnimation(this);
                }
            });
        });

        // Add hover effects to product cards
        document.querySelectorAll('.product-card').forEach(function(card) {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Size selection functionality with dynamic pricing
        document.querySelectorAll('.size-option').forEach(function(option) {
            option.addEventListener('click', function() {
                // Remove selected class from all options in the same group
                const group = this.closest('.size-options');
                group.querySelectorAll('.size-option').forEach(function(opt) {
                    opt.classList.remove('selected');
                });
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Check the radio button
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
                
                // Update price based on selected size
                updateProductPrice(this);
            });
        });
        
        // Function to update product price based on selected size
        function updateProductPrice(selectedOption) {
            const productCard = selectedOption.closest('.product-card');
            const productId = productCard.dataset.productId;
            const basePrice = parseFloat(productCard.dataset.basePrice);
            const finalPrice = parseFloat(selectedOption.dataset.finalPrice);
            const priceElement = document.getElementById('price-' + productId);
            
            if (priceElement) {
                // Add updating animation
                priceElement.classList.add('updating');
                
                // Update price with animation
                setTimeout(function() {
                    const priceDifference = finalPrice - basePrice;
                    let priceText = '₱' + finalPrice.toFixed(2);
                    
                    // Add price indicator if there's a difference
                    if (priceDifference !== 0) {
                        const indicatorClass = priceDifference > 0 ? 'price-increase' : 'price-decrease';
                        const indicatorText = priceDifference > 0 ? '+' : '';
                        priceText += '<div class="price-indicator ' + indicatorClass + '">' + 
                                   indicatorText + '₱' + Math.abs(priceDifference).toFixed(2) + '</div>';
                    }
                    
                    priceElement.innerHTML = priceText;
                    priceElement.classList.remove('updating');
                }, 150);
            }
        }

        // Initialize size selection on page load
        document.querySelectorAll('.size-option input[type="radio"]:checked').forEach(function(radio) {
            const selectedOption = radio.closest('.size-option');
            selectedOption.classList.add('selected');
            
            // Update price for initially selected size
            updateProductPrice(selectedOption);
        });

        // Add notification system
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span class="notification-icon">${type === 'success' ? '✅' : '❌'}</span>
                    <span class="notification-message">${message}</span>
                    <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
            `;
            
            // Add styles
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? 'linear-gradient(135deg, #d4edda, #c3e6cb)' : 'linear-gradient(135deg, #f8d7da, #f5c6cb)'};
                color: ${type === 'success' ? '#155724' : '#721c24'};
                padding: 1rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 1000;
                animation: slideInRight 0.3s ease-out;
                max-width: 300px;
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 4 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.animation = 'slideOutRight 0.3s ease-out';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 4000);
        }

        // Enhanced promo code validation
        function validatePromoCode(code) {
            if (!code || code.trim() === '') {
                return { valid: false, message: 'Please enter a promo code.' };
            }
            
            // Basic validation - check if it's not too long
            if (code.length > 20) {
                return { valid: false, message: 'Promo code is too long.' };
            }
            
            // Check for valid characters (alphanumeric and some special chars)
            if (!/^[A-Z0-9_-]+$/i.test(code)) {
                return { valid: false, message: 'Promo code contains invalid characters.' };
            }
            
            return { valid: true, message: '' };
        }

        // Add promo code input validation
        document.addEventListener('DOMContentLoaded', function() {
            const promoInput = document.querySelector('input[name="promo_code"]');
            if (promoInput) {
                promoInput.addEventListener('input', function() {
                    const code = this.value.trim();
                    const validation = validatePromoCode(code);
                    
                    if (code.length > 0 && !validation.valid) {
                        this.style.borderColor = '#dc3545';
                        this.style.backgroundColor = '#fff5f5';
                    } else {
                        this.style.borderColor = '#ddd';
                        this.style.backgroundColor = '';
                    }
                });
                
                // Convert to uppercase on blur
                promoInput.addEventListener('blur', function() {
                    this.value = this.value.toUpperCase();
                });
            }
        });

        // Add CSS for notification animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    opacity: 0;
                    transform: translateX(100%);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            @keyframes slideOutRight {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(100%);
                }
            }
            
            .notification-content {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .notification-close {
                background: none;
                border: none;
                font-size: 1.2rem;
                cursor: pointer;
                margin-left: auto;
                opacity: 0.7;
            }
            
            .notification-close:hover {
                opacity: 1;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>