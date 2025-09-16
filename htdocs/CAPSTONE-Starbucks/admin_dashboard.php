<?php
session_start();
require_once 'db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle order status updates
if (isset($_POST['update_order_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['new_status'];
    
    // Validate status
    $valid_statuses = ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled', 'refunded'];
    if (in_array($new_status, $valid_statuses)) {
        $update_query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
        mysqli_stmt_execute($stmt);
        
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $success_message = "Order #$order_id status updated to " . ucfirst($new_status) . ".";
            // Redirect to prevent form resubmission
            header("Location: admin_dashboard.php?updated=1&order_id=$order_id&status=$new_status");
            exit();
        } else {
            $error_message = "Failed to update order status.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle bulk order status updates
if (isset($_POST['bulk_update_status'])) {
    $bulk_action = $_POST['bulk_action'];
    $new_status = '';
    $current_status = '';
    
    switch ($bulk_action) {
        case 'start_preparing':
            $new_status = 'preparing';
            $current_status = 'pending';
            break;
        case 'mark_ready':
            $new_status = 'ready';
            $current_status = 'pending';
            break;
        case 'complete':
            $new_status = 'completed';
            $current_status = 'pending';
            break;
        case 'preparing_to_ready':
            $new_status = 'ready';
            $current_status = 'preparing';
            break;
        case 'preparing_to_complete':
            $new_status = 'completed';
            $current_status = 'preparing';
            break;
        case 'preparing_to_cancel':
            $new_status = 'cancelled';
            $current_status = 'preparing';
            break;
        case 'ready_to_complete':
            $new_status = 'completed';
            $current_status = 'ready';
            break;
        case 'ready_to_cancel':
            $new_status = 'cancelled';
            $current_status = 'ready';
            break;
        case 'completed_to_cancel':
            $new_status = 'cancelled';
            $current_status = 'completed';
            break;
    }
    
    if ($new_status && $current_status) {
        $bulk_update_query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE status = ?";
        $stmt = mysqli_prepare($connection, $bulk_update_query);
        mysqli_stmt_bind_param($stmt, "ss", $new_status, $current_status);
        mysqli_stmt_execute($stmt);
        $affected_rows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        
        if ($affected_rows > 0) {
            $success_message = "Successfully updated $affected_rows " . ucfirst($current_status) . " orders to " . ucfirst($new_status) . " status.";
            // Redirect to prevent form resubmission
            header("Location: admin_dashboard.php?bulk_updated=1&count=$affected_rows&from=$current_status&to=$new_status");
            exit();
        } else {
            $error_message = "No orders found with " . ucfirst($current_status) . " status to update.";
        }
    }
}


// Get dashboard statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()) as today_orders,
        (SELECT COALESCE(SUM(final_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'completed') as today_sales,
        (SELECT COUNT(*) FROM orders WHERE status = 'pending') as pending_orders,
        (SELECT COUNT(*) FROM orders WHERE status = 'preparing') as preparing_orders,
        (SELECT COUNT(*) FROM orders WHERE status = 'ready') as ready_orders,
        (SELECT COUNT(*) FROM orders WHERE status = 'completed') as completed_orders,
        (SELECT COUNT(*) FROM orders WHERE status = 'cancelled') as cancelled_orders,
        (SELECT COUNT(*) FROM customers) as total_customers,
        (SELECT COUNT(*) FROM products WHERE stock_quantity <= min_stock_level) as low_stock_products
";
$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get recent orders - Enhanced with more details
$orders_query = "
    SELECT o.*, 
           CONCAT(c.first_name, ' ', c.last_name) as customer_name,
           c.phone as customer_phone,
           c.email as customer_email,
           (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    ORDER BY o.created_at DESC 
    LIMIT 20
";
$orders_result = mysqli_query($connection, $orders_query);

// Get sales data for chart (last 7 days)
$sales_query = "
    SELECT 
        DATE(created_at) as date,
        COALESCE(SUM(final_amount), 0) as daily_sales,
        COUNT(*) as order_count
    FROM orders 
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
    AND status = 'completed'
    GROUP BY DATE(created_at)
    ORDER BY date ASC
";
$sales_result = mysqli_query($connection, $sales_query);
$sales_data = array();
while ($row = mysqli_fetch_assoc($sales_result)) {
    $sales_data[] = $row;
}

// Get top selling products - Fixed query
$products_query = "
    SELECT 
        oi.product_name,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.total_price) as total_revenue,
        COUNT(DISTINCT o.id) as order_count,
        AVG(oi.unit_price) as avg_price
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed' 
    AND DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY oi.product_name
    ORDER BY total_quantity DESC
    LIMIT 5
";
$products_result = mysqli_query($connection, $products_query);


// Get low stock alerts
$low_stock_query = "SELECT id, name, stock_quantity, min_stock_level FROM products WHERE stock_quantity <= min_stock_level AND status = 'active' ORDER BY stock_quantity ASC";
$low_stock_result = mysqli_query($connection, $low_stock_query);
$low_stock_products = array();
if ($low_stock_result) {
    while ($row = mysqli_fetch_assoc($low_stock_result)) {
        $low_stock_products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Starbucks Management System</title>
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

        .admin-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-info span {
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


        .low-stock-alert {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .low-stock-title {
            color: #856404;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .low-stock-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #ffeaa7;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #00704a;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #00704a;
        }

        .stat-card.pending { border-left-color: #ffc107; }
        .stat-card.pending .value { color: #ffc107; }

        .stat-card.preparing { border-left-color: #fd7e14; }
        .stat-card.preparing .value { color: #fd7e14; }

        .stat-card.ready { border-left-color: #20c997; }
        .stat-card.ready .value { color: #20c997; }

        .stat-card.completed { border-left-color: #28a745; }
        .stat-card.completed .value { color: #28a745; }

        .stat-card.cancelled { border-left-color: #dc3545; }
        .stat-card.cancelled .value { color: #dc3545; }

        .stat-card.low-stock { border-left-color: #e74c3c; }
        .stat-card.low-stock .value { color: #e74c3c; }

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
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            background: #f8f9fa;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            border-bottom: 2px solid #dee2e6;
        }

        .status-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-preparing {
            background: #ffeaa7;
            color: #d68910;
        }

        .status-ready {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .status-refunded {
            background: #e2e3e5;
            color: #383d41;
        }

        .status-select {
            padding: 0.4rem 0.8rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: white;
            font-size: 0.85rem;
            font-weight: 500;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .status-select:focus {
            outline: none;
            border-color: #00704a;
            box-shadow: 0 0 0 3px rgba(0, 112, 74, 0.1);
        }

        .status-select:hover {
            border-color: #00704a;
            transform: translateY(-1px);
        }

        .status-select option {
            padding: 0.5rem;
            background: white;
            color: #333;
        }

        .status-select option:checked {
            background: #00704a;
            color: white;
        }

        .order-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .quick-status-btn {
            padding: 0.3rem 0.6rem;
            border: none;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .quick-status-btn.pending {
            background: #fff3cd;
            color: #856404;
        }

        .quick-status-btn.preparing {
            background: #ffeaa7;
            color: #d68910;
        }

        .quick-status-btn.ready {
            background: #d1ecf1;
            color: #0c5460;
        }

        .quick-status-btn.completed {
            background: #d4edda;
            color: #155724;
        }

        .quick-status-btn.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .quick-status-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .actions-cell {
            min-width: 180px;
            text-align: center;
        }

        .status-form {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: center;
        }

        .status-form .status-select {
            width: 100%;
            max-width: 150px;
            font-size: 0.8rem;
            padding: 0.5rem 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .status-form .status-select:hover {
            border-color: #00704a;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 112, 74, 0.2);
        }

        .status-form .status-select:focus {
            outline: none;
            border-color: #00704a;
            box-shadow: 0 0 0 3px rgba(0, 112, 74, 0.1);
        }

        .quick-actions {
            display: flex;
            gap: 0.3rem;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 0.5rem;
        }

        .quick-action-btn {
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }

        .quick-action-btn.pending {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .quick-action-btn.preparing {
            background: linear-gradient(135deg, #ffeaa7, #ffd93d);
            color: #d68910;
            border: 1px solid #ffd93d;
        }

        .quick-action-btn.ready {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .quick-action-btn.completed {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .quick-action-btn.cancelled {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .quick-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0,0,0,0.15);
        }

        .action-label {
            font-size: 0.75rem;
            color: #666;
            margin-bottom: 0.3rem;
            font-weight: 500;
        }

        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.3rem;
        }

        .status-indicator.pending { background: #ffc107; }
        .status-indicator.preparing { background: #fd7e14; }
        .status-indicator.ready { background: #20c997; }
        .status-indicator.completed { background: #28a745; }
        .status-indicator.cancelled { background: #dc3545; }
        .status-indicator.refunded { background: #6c757d; }

        .order-actions-container {
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            padding: 0.8rem;
            border-radius: 10px;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .actions-cell {
                min-width: 200px;
            }

            .order-actions-container {
                padding: 0.6rem;
            }

            .status-form .status-select {
                font-size: 0.75rem;
                padding: 0.4rem 0.6rem;
            }

            .quick-actions {
                gap: 0.2rem;
            }

            .quick-action-btn {
                font-size: 0.65rem;
                padding: 0.2rem 0.4rem;
                min-width: 50px;
            }

            .action-label {
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>☕ Starbucks Admin</h1>
                <p>Management Dashboard</p>
            </div>
            <div class="admin-info">
                <span>Welcome, <?php echo isset($_SESSION['admin_name']) ? htmlspecialchars($_SESSION['admin_name']) : 'Admin'; ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <!-- Navigation Menu -->
    <nav class="nav-menu">
        <div class="nav-content">
            <a href="admin_dashboard.php" class="nav-item active">
                🏠 Dashboard
            </a>
            <a href="admin_add_stock.php" class="nav-item">
                📦 Add Stock
            </a>
            <a href="admin_add_product.php" class="nav-item">
                ➕ Add Product
            </a>
            <a href="admin_view_products.php" class="nav-item">
                📋 View Products
            </a>
            <a href="admin_view_customers.php" class="nav-item">
                👥 View Customers
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

        <!-- URL Parameter Success Messages -->
        <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
        <div class="success-message">
            ✅ Order #<?php echo htmlspecialchars($_GET['order_id']); ?> status updated to <?php echo ucfirst(htmlspecialchars($_GET['status'])); ?>!
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['bulk_updated']) && $_GET['bulk_updated'] == '1'): ?>
        <div class="success-message">
            ✅ Successfully updated <?php echo htmlspecialchars($_GET['count']); ?> orders from <?php echo ucfirst(htmlspecialchars($_GET['from'])); ?> to <?php echo ucfirst(htmlspecialchars($_GET['to'])); ?>!
        </div>
        <?php endif; ?>

        <!-- Low Stock Alert -->
        <?php if (!empty($low_stock_products)): ?>
        <div class="low-stock-alert">
            <div class="low-stock-title">⚠️ Low Stock Alert</div>
            <?php foreach ($low_stock_products as $product): ?>
            <div class="low-stock-item">
                <span><?php echo htmlspecialchars($product['name']); ?></span>
                <span>Stock: <?php echo $product['stock_quantity']; ?> / Min: <?php echo $product['min_stock_level']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Today's Orders</h3>
                <div class="value"><?php echo number_format($stats['today_orders']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Today's Sales</h3>
                <div class="value">₱<?php echo number_format($stats['today_sales'], 2); ?></div>
            </div>
            <div class="stat-card pending">
                <h3>Pending Orders</h3>
                <div class="value"><?php echo number_format($stats['pending_orders']); ?></div>
            </div>
            <div class="stat-card preparing">
                <h3>Preparing Orders</h3>
                <div class="value"><?php echo number_format($stats['preparing_orders']); ?></div>
            </div>
            <div class="stat-card ready">
                <h3>Ready Orders</h3>
                <div class="value"><?php echo number_format($stats['ready_orders']); ?></div>
            </div>
            <div class="stat-card completed">
                <h3>Completed Orders</h3>
                <div class="value"><?php echo number_format($stats['completed_orders']); ?></div>
            </div>
            <div class="stat-card cancelled">
                <h3>Cancelled Orders</h3>
                <div class="value"><?php echo number_format($stats['cancelled_orders']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Customers</h3>
                <div class="value"><?php echo number_format($stats['total_customers']); ?></div>
            </div>
            <div class="stat-card low-stock">
                <h3>Low Stock Items</h3>
                <div class="value"><?php echo number_format($stats['low_stock_products']); ?></div>
            </div>
        </div>

        <!-- Bulk Order Management -->
        <div class="section">
            <h2>⚡ Quick Order Management</h2>
            <form method="POST" action="" style="margin-bottom: 1.5rem;">
                <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <label for="bulk_action" style="font-weight: 500;">Bulk Actions:</label>
                    <select name="bulk_action" id="bulk_action" style="padding: 0.5rem; border: 2px solid #ddd; border-radius: 5px; min-width: 200px;">
                        <option value="">Select Action</option>
                        <option value="start_preparing">Start Preparing All Pending</option>
                        <option value="mark_ready">Mark All Pending as Ready</option>
                        <option value="complete">Complete All Pending</option>
                        <option value="preparing_to_ready">Mark Preparing as Ready</option>
                        <option value="preparing_to_complete">Complete All Preparing</option>
                        <option value="ready_to_complete">Complete All Ready</option>
                    </select>
                    <button type="submit" name="bulk_update_status" class="btn btn-primary" style="padding: 0.5rem 1rem; background: #00704a; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        Execute Bulk Action
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Orders Section -->
        <div class="section">
            <h2>📋 Recent Orders</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Reset the result pointer to ensure we can loop through results
                        mysqli_data_seek($orders_result, 0);
                        while ($order = mysqli_fetch_assoc($orders_result)): 
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($order['customer_name']); ?>
                                <br><small style="color: #666;"><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                            </td>
                            <td>
                                <?php echo $order['item_count']; ?> items
                                <?php
                                // Fetch items with selected sizes for this order
                                $items_stmt = $connection->prepare("SELECT product_name, quantity, COALESCE(selected_size, size) AS item_size FROM order_items WHERE order_id = ? ORDER BY id ASC");
                                if ($items_stmt) {
                                    $items_stmt->bind_param("i", $order['id']);
                                    $items_stmt->execute();
                                    $items_result = $items_stmt->get_result();
                                    if ($items_result && $items_result->num_rows > 0) {
                                        echo '<div style="margin-top:6px; display:flex; flex-direction:column; gap:4px;">';
                                        while ($it = $items_result->fetch_assoc()) {
                                            $displaySize = $it['item_size'] !== null && $it['item_size'] !== '' && $it['item_size'] !== '0' ? htmlspecialchars($it['item_size']) : 'One Size';
                                            echo '<div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">'
                                                . '<span style="background:#e8f5e8;color:#00704a;padding:2px 6px;border-radius:10px;font-size:0.75rem;font-weight:700;">' . (int)$it['quantity'] . 'x</span>'
                                                . '<span style="font-weight:600;color:#333;">' . htmlspecialchars($it['product_name']) . '</span>'
                                                . '<span style="background:#d1ecf1;color:#0c5460;padding:2px 6px;border-radius:10px;font-size:0.72rem;font-weight:700;">' . $displaySize . '</span>'
                                              . '</div>';
                                        }
                                        echo '</div>';
                                    }
                                    $items_stmt->close();
                                }
                                ?>
                            </td>
                            <td>
                                ₱<?php echo number_format($order['final_amount'], 2); ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                            </td>
                            <td class="actions-cell">
                                <div class="order-actions-container">
                                    <div class="action-label">
                                        <span class="status-indicator status-<?php echo $order['status']; ?>"></span>
                                        Update Status
                                    </div>
                                    <form method="POST" action="" class="status-form">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="new_status" class="status-select" onchange="this.form.submit()">
                                            <option value="">Select Status</option>
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                                            <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>✅ Confirmed</option>
                                            <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>👨‍🍳 Preparing</option>
                                            <option value="ready" <?php echo $order['status'] == 'ready' ? 'selected' : ''; ?>>📦 Ready</option>
                                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>✅ Completed</option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
                                            <option value="refunded" <?php echo $order['status'] == 'refunded' ? 'selected' : ''; ?>>💰 Refunded</option>
                                        </select>
                                        <input type="hidden" name="update_order_status" value="1">
                                    </form>
                                    <div class="quick-actions">
                                        <button type="button" class="quick-action-btn pending" onclick="quickStatusChange(<?php echo $order['id']; ?>, 'pending')">Pending</button>
                                        <button type="button" class="quick-action-btn preparing" onclick="quickStatusChange(<?php echo $order['id']; ?>, 'preparing')">Preparing</button>
                                        <button type="button" class="quick-action-btn ready" onclick="quickStatusChange(<?php echo $order['id']; ?>, 'ready')">Ready</button>
                                        <button type="button" class="quick-action-btn completed" onclick="quickStatusChange(<?php echo $order['id']; ?>, 'completed')">Complete</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Products Section -->
        <div class="section">
            <h2>🏆 Top Selling Products (Last 30 Days)</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity Sold</th>
                            <th>Revenue</th>
                            <th>Orders</th>
                            <th>Avg Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                            </td>
                            <td>
                                <?php echo number_format($product['total_quantity']); ?>
                            </td>
                            <td>
                                ₱<?php echo number_format($product['total_revenue'], 2); ?>
                            </td>
                            <td>
                                <?php echo number_format($product['order_count']); ?>
                            </td>
                            <td>
                                ₱<?php echo number_format($product['avg_price'], 2); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide success/error messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.success-message, .error-message');
            messages.forEach(function(message) {
                message.style.transition = 'opacity 0.5s';
                message.style.opacity = '0';
                setTimeout(function() {
                    message.remove();
                }, 500);
            });
        }, 5000);


        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + S to go to Add Stock
            if (e.altKey && e.key === 's') {
                e.preventDefault();
                window.location.href = 'admin_add_stock.php';
            }
            
            // Alt + P to go to Add Product
            if (e.altKey && e.key === 'p') {
                e.preventDefault();
                window.location.href = 'admin_add_product.php';
            }
            
            // Alt + V to go to View Products
            if (e.altKey && e.key === 'v') {
                e.preventDefault();
                window.location.href = 'admin_view_products.php';
            }
            
            // Alt + C to go to View Customers
            if (e.altKey && e.key === 'c') {
                e.preventDefault();
                window.location.href = 'admin_view_customers.php';
            }
        });

        // Initialize tooltips for low stock items
        document.querySelectorAll('.low-stock-item').forEach(function(item) {
            item.title = 'This item needs restocking soon';
        });

        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            window.location.reload();
        }, 300000);

        // Add visual feedback for form interactions
        document.querySelectorAll('select, input').forEach(function(element) {
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

        // Mobile navigation toggle
        function toggleMobileNav() {
            const navItems = document.querySelector('.nav-items');
            const navToggle = document.querySelector('.nav-toggle');
            
            navItems.classList.toggle('show');
            navToggle.classList.toggle('active');
            
            // Animate hamburger menu
            const spans = navToggle.querySelectorAll('span');
            if (navToggle.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        }

        // Close mobile nav when clicking outside
        document.addEventListener('click', function(e) {
            const nav = document.querySelector('.main-nav');
            const navItems = document.querySelector('.nav-items');
            const navToggle = document.querySelector('.nav-toggle');
            
            if (!nav.contains(e.target) && navItems.classList.contains('show')) {
                navItems.classList.remove('show');
                navToggle.classList.remove('active');
                
                // Reset hamburger menu
                const spans = navToggle.querySelectorAll('span');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });

        // Add click handlers for stat cards (optional navigation)
        document.querySelectorAll('.stat-card').forEach(function(card) {
            card.style.cursor = 'pointer';
            card.addEventListener('click', function() {
                const title = this.querySelector('h3').textContent.toLowerCase();
                
                // Navigate based on card type
                if (title.includes('pending')) {
                    window.location.href = 'manage_orders.php?filter=pending';
                } else if (title.includes('preparing')) {
                    window.location.href = 'manage_orders.php?filter=preparing';
                } else if (title.includes('ready')) {
                    window.location.href = 'manage_orders.php?filter=ready';
                } else if (title.includes('completed')) {
                    window.location.href = 'manage_orders.php?filter=completed';
                } else if (title.includes('cancelled')) {
                    window.location.href = 'manage_orders.php?filter=cancelled';
                } else if (title.includes('customers')) {
                    window.location.href = 'manage_customers.php';
                } else if (title.includes('stock')) {
                    window.location.href = 'inventory.php?filter=low_stock';
                }
            });
        });

        // Notification system for real-time updates
        function checkForUpdates() {
            // This would typically make an AJAX call to check for new orders, etc.
            // For now, we'll just update the page title with pending orders count
            const pendingOrders = <?php echo $stats['pending_orders']; ?>;
            if (pendingOrders > 0) {
                document.title = `(${pendingOrders}) Admin Dashboard - Starbucks`;
            } else {
                document.title = 'Admin Dashboard - Starbucks Management System';
            }
        }

        // Check for updates every 30 seconds
        setInterval(checkForUpdates, 30000);

        // Initialize
        checkForUpdates();

        // Add loading animation for quick action buttons
        document.querySelectorAll('.quick-action-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                // Add loading state
                this.style.opacity = '0.7';
                this.style.transform = 'scale(0.98)';
                
                // Reset after a short delay
                setTimeout(() => {
                    this.style.opacity = '1';
                    this.style.transform = 'scale(1)';
                }, 200);
            });
        });

        // Enhanced keyboard navigation
        document.addEventListener('keydown', function(e) {
            // Alt + D for Dashboard
            if (e.altKey && e.key === 'd') {
                e.preventDefault();
                window.location.href = 'admin_dashboard.php';
            }
            
            // Alt + O for Orders
            if (e.altKey && e.key === 'o') {
                e.preventDefault();
                window.location.href = 'manage_orders.php';
            }
            
            // Alt + P for Products
            if (e.altKey && e.key === 'p') {
                e.preventDefault();
                window.location.href = 'manage_products.php';
            }
            
            // Alt + C for Customers
            if (e.altKey && e.key === 'c') {
                e.preventDefault();
                window.location.href = 'manage_customers.php';
            }
            
            // Alt + R for Reports
            if (e.altKey && e.key === 'r') {
                e.preventDefault();
                window.location.href = 'reports.php';
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

        // Order status update confirmation
        document.querySelectorAll('.status-select').forEach(function(select) {
            select.addEventListener('change', function(e) {
                const newStatus = this.value;
                const orderId = this.form.querySelector('input[name="order_id"]').value;
                const currentStatus = this.closest('tr').querySelector('.status-badge').textContent.trim();
                
                if (newStatus && newStatus !== currentStatus.toLowerCase()) {
                    if (confirm(`Are you sure you want to change order #${orderId} from "${currentStatus}" to "${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}"?`)) {
                        // Add loading state
                        this.style.opacity = '0.7';
                        this.style.pointerEvents = 'none';
                        
                        // Submit the form
                        this.form.submit();
                    } else {
                        // Reset to current status
                        this.value = '';
                    }
                }
            });
        });

        // Bulk action confirmation
        document.querySelector('form[method="POST"]').addEventListener('submit', function(e) {
            const bulkAction = document.getElementById('bulk_action').value;
            if (bulkAction) {
                const actionText = document.getElementById('bulk_action').selectedOptions[0].text;
                if (!confirm(`Are you sure you want to ${actionText.toLowerCase()}?`)) {
                    e.preventDefault();
                }
            }
        });

        // Add visual feedback for status changes
        function addStatusChangeFeedback() {
            const statusSelects = document.querySelectorAll('.status-select');
            statusSelects.forEach(function(select) {
                select.addEventListener('change', function() {
                    const row = this.closest('tr');
                    row.style.transition = 'all 0.3s ease';
                    row.style.backgroundColor = '#e8f5e8';
                    
                    setTimeout(function() {
                        row.style.backgroundColor = '';
                    }, 2000);
                });
            });
        }

        // Initialize status change feedback
        addStatusChangeFeedback();

        // Auto-refresh after status update
        if (window.location.search.includes('updated=1')) {
            setTimeout(function() {
                window.location.href = window.location.pathname;
            }, 3000);
        }

        // Add keyboard shortcuts for common status changes
        document.addEventListener('keydown', function(e) {
            // Ctrl + 1 for Pending
            if (e.ctrlKey && e.key === '1') {
                e.preventDefault();
                document.querySelectorAll('.status-select').forEach(function(select) {
                    if (select.value === '') {
                        select.value = 'pending';
                        select.dispatchEvent(new Event('change'));
                    }
                });
            }
            
            // Ctrl + 2 for Preparing
            if (e.ctrlKey && e.key === '2') {
                e.preventDefault();
                document.querySelectorAll('.status-select').forEach(function(select) {
                    if (select.value === '') {
                        select.value = 'preparing';
                        select.dispatchEvent(new Event('change'));
                    }
                });
            }
            
            // Ctrl + 3 for Ready
            if (e.ctrlKey && e.key === '3') {
                e.preventDefault();
                document.querySelectorAll('.status-select').forEach(function(select) {
                    if (select.value === '') {
                        select.value = 'ready';
                        select.dispatchEvent(new Event('change'));
                    }
                });
            }
            
            // Ctrl + 4 for Completed
            if (e.ctrlKey && e.key === '4') {
                e.preventDefault();
                document.querySelectorAll('.status-select').forEach(function(select) {
                    if (select.value === '') {
                        select.value = 'completed';
                        select.dispatchEvent(new Event('change'));
                    }
                });
            }
        });

        // Add tooltip for keyboard shortcuts
        document.querySelectorAll('.status-select').forEach(function(select) {
            select.title = 'Keyboard shortcuts: Ctrl+1=Pending, Ctrl+2=Preparing, Ctrl+3=Ready, Ctrl+4=Completed';
        });

        // Quick status change function
        function quickStatusChange(orderId, newStatus) {
            if (confirm(`Are you sure you want to change order #${orderId} to "${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}"?`)) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const orderIdInput = document.createElement('input');
                orderIdInput.type = 'hidden';
                orderIdInput.name = 'order_id';
                orderIdInput.value = orderId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'new_status';
                statusInput.value = newStatus;
                
                const updateInput = document.createElement('input');
                updateInput.type = 'hidden';
                updateInput.name = 'update_order_status';
                updateInput.value = '1';
                
                form.appendChild(orderIdInput);
                form.appendChild(statusInput);
                form.appendChild(updateInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Add hover effects to action containers
        document.querySelectorAll('.order-actions-container').forEach(function(container) {
            container.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            });
            
            container.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';
            });
        });

        // Add loading state to quick action buttons
        document.querySelectorAll('.quick-action-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                this.style.opacity = '0.7';
                this.style.transform = 'scale(0.95)';
                
                setTimeout(() => {
                    this.style.opacity = '1';
                    this.style.transform = 'scale(1)';
                }, 200);
            });
        });
    </script>
</body>
</html>