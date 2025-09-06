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
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    $update_query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($connection, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Get dashboard statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()) as today_orders,
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'completed') as today_sales,
        (SELECT COUNT(*) FROM orders WHERE status = 'pending') as pending_orders,
        (SELECT COUNT(*) FROM customers) as total_customers
";
$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get recent orders
$orders_query = "
    SELECT o.*, 
           CONCAT(c.first_name, ' ', c.last_name) as customer_name,
           c.phone as customer_phone
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
        COALESCE(SUM(total_amount), 0) as daily_sales,
        COUNT(*) as order_count
    FROM orders 
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
    AND status = 'completed'
    GROUP BY DATE(created_at)
    ORDER BY date ASC
";
$sales_result = mysqli_query($connection, $sales_query);
$sales_data = [];
while ($row = mysqli_fetch_assoc($sales_result)) {
    $sales_data[] = $row;
}

// Get top selling products
$products_query = "
    SELECT 
        oi.product_name,
        SUM(oi.quantity) as total_quantity,
        SUM(o.total_amount) as total_revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed' 
    AND DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY oi.product_name, o.id
    ORDER BY total_quantity DESC
    LIMIT 5
";
$products_result = mysqli_query($connection, $products_query);
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
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #00704A, #008B5A);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo h1 {
            font-size: 1.8rem;
            margin-bottom: 0.2rem;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background: #c82333;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #666;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #00704A;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: #333;
            border-bottom: 2px solid #00704A;
            padding-bottom: 0.5rem;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .orders-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-preparing { background: #d1ecf1; color: #0c5460; }
        .status-ready { background: #d4edda; color: #155724; }
        .status-completed { background: #e2e3e5; color: #383d41; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .status-select {
            padding: 0.3rem;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 0.8rem;
        }
        
        .update-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.3rem 0.8rem;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .update-btn:hover {
            background: #0056b3;
        }
        
        .sales-chart {
            height: 300px;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 1rem 0;
        }
        
        .product-list {
            list-style: none;
        }
        
        .product-item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem;
            border-bottom: 1px solid #eee;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-name {
            font-weight: bold;
        }
        
        .product-stats {
            font-size: 0.9rem;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .orders-table {
                font-size: 0.8rem;
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
                <span>Welcome, <?php echo $_SESSION['admin_name']; ?></span>
                <span>(<?php echo $_SESSION['employee_id']; ?>)</span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Today's Orders</h3>
                <div class="value"><?php echo $stats['today_orders']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Today's Sales</h3>
                <div class="value">$<?php echo number_format($stats['today_sales'], 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <div class="value"><?php echo $stats['pending_orders']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Customers</h3>
                <div class="value"><?php echo $stats['total_customers']; ?></div>
            </div>
        </div>

        <div class="main-content">
            <!-- Orders Management -->
            <div class="section">
                <h2 class="section-title">📋 Recent Orders</h2>
                <div style="overflow-x: auto;">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="new_status" class="status-select">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                            <option value="ready" <?php echo $order['status'] == 'ready' ? 'selected' : ''; ?>>Ready</option>
                                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_order_status" class="update-btn">Update</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sidebar Content -->
            <div>
                <!-- Sales Overview -->
                <div class="section">
                    <h2 class="section-title">📊 Sales Overview (7 Days)</h2>
                    <div class="sales-chart">
                        <div style="text-align: center;">
                            <p><strong>Weekly Sales Trend</strong></p>
                            <?php if (!empty($sales_data)): ?>
                                <?php
                                $total_week_sales = array_sum(array_column($sales_data, 'daily_sales'));
                                $total_week_orders = array_sum(array_column($sales_data, 'order_count'));
                                ?>
                                <p>Total: $<?php echo number_format($total_week_sales, 2); ?></p>
                                <p>Orders: <?php echo $total_week_orders; ?></p>
                                <small style="color: #666;">Chart visualization would go here</small>
                            <?php else: ?>
                                <p>No sales data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="section">
                    <h2 class="section-title">🏆 Top Products (30 Days)</h2>
                    <ul class="product-list">
                        <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                        <li class="product-item">
                            <div>
                                <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                                <div class="product-stats"><?php echo $product['total_quantity']; ?> sold</div>
                            </div>
                            <div class="product-stats">
                                $<?php echo number_format($product['total_revenue'], 2); ?>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section">
            <h2 class="section-title">⚡ Quick Actions</h2>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="add_product.php" style="background: #28a745; color: white; padding: 0.8rem 1.5rem; text-decoration: none; border-radius: 5px;">Add New Product</a>
                <a href="view_customers.php" style="background: #17a2b8; color: white; padding: 0.8rem 1.5rem; text-decoration: none; border-radius: 5px;">View All Customers</a>
                <a href="sales_report.php" style="background: #ffc107; color: #212529; padding: 0.8rem 1.5rem; text-decoration: none; border-radius: 5px;">Generate Report</a>
                <a href="inventory.php" style="background: #6f42c1; color: white; padding: 0.8rem 1.5rem; text-decoration: none; border-radius: 5px;">Manage Inventory</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh the page every 5 minutes to update order status
        setTimeout(function() {
            location.reload();
        }, 300000);
        
        // Add confirmation for order status updates
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (this.querySelector('select[name="new_status"]').value === 'cancelled') {
                    if (!confirm('Are you sure you want to cancel this order?')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>