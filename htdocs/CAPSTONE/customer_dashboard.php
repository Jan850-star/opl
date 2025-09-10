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

// Get customer dashboard statistics
$customer_stats_query = "
    SELECT
        (SELECT COUNT(*) FROM orders WHERE customer_id = ?) as total_orders,
        (SELECT COALESCE(SUM(final_amount), 0) FROM orders WHERE customer_id = ? AND status = 'completed') as total_spent,
        (SELECT COUNT(*) FROM orders WHERE customer_id = ? AND status = 'pending') as pending_orders,
        (SELECT COALESCE(SUM(points), 0) FROM loyalty_points WHERE customer_id = ?) as loyalty_points
";
$stmt_stats = mysqli_prepare($connection, $customer_stats_query);
mysqli_stmt_bind_param($stmt_stats, "iiii", $customer_id, $customer_id, $customer_id, $customer_id);
mysqli_stmt_execute($stmt_stats);
$result_stats = mysqli_stmt_get_result($stmt_stats);
$customer_stats = mysqli_fetch_assoc($result_stats);
mysqli_stmt_close($stmt_stats);

// Get recent orders for the customer
$orders_query = "
    SELECT id, order_number, total_amount, status, created_at 
    FROM orders 
    WHERE customer_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
";
$stmt_orders = mysqli_prepare($connection, $orders_query);
mysqli_stmt_bind_param($stmt_orders, "i", $customer_id);
mysqli_stmt_execute($stmt_orders);
$orders_result = mysqli_stmt_get_result($stmt_orders);
$orders = mysqli_fetch_all($orders_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt_orders);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Starbucks</title>
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
            padding: 1rem 2rem 0.5rem 2rem;
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
        
        .customer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .menu-bar {
            display: flex;
            gap: 1rem;
            margin-top: 0.7rem;
            margin-bottom: 0.7rem;
            justify-content: flex-end;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        .menu-link, .logout-btn, .primary-action-btn {
            text-decoration: none;
            border-radius: 5px;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            transition: background-color 0.3s;
            display: inline-block;
        }
        .menu-link {
            background: #17a2b8;
            color: white;
        }
        .menu-link:hover {
            background: #138496;
        }
        .edit-profile-link {
            background: #ffc107;
            color: #212529;
        }
        .edit-profile-link:hover {
            background: #e0a800;
        }
        .primary-action-btn {
            background: #00704A !important; /* Starbucks Green */
            font-size: 1.1rem !important;
            padding: 1rem 2rem !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 10px rgba(0, 112, 74, 0.3);
            transition: background-color 0.3s ease, transform 0.2s ease;
            color: white;
        }
        .primary-action-btn:hover {
            background: #005f3d !important;
            transform: translateY(-2px);
        }
        .logout-btn {
            background: #dc3545;
            color: white;
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
            grid-template-columns: 1fr;
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
            .menu-bar {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>Your Dashboard</h1>
                <p>Welcome to Starbucks!</p>
            </div>
            <div class="customer-info">
                <span>Welcome, <?php echo $customer_username; ?>!</span>
            </div>
        </div>
        <!-- Menu Bar at the Top -->
        <nav class="menu-bar">
            <a href="customer_place_order.php" class="primary-action-btn">Order a Product</a>
            <a href="customer_product.php" class="menu-link">View All Products</a>
            <a href="customer_profile.php" class="edit-profile-link menu-link">Edit Profile</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>
    </header>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="value"><?php echo $customer_stats['total_orders']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Spent</h3>
                <div class="value">$<?php echo number_format($customer_stats['total_spent'], 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <div class="value"><?php echo $customer_stats['pending_orders']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Loyalty Points</h3>
                <div class="value"><?php echo $customer_stats['loyalty_points']; ?></div>
            </div>
        </div>

        <div class="main-content">
            <!-- Recent Orders -->
            <div class="section">
                <h2 class="section-title">📋 Your Recent Orders</h2>
                <div style="overflow-x: auto;">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order Number</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Order Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($orders)): ?>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No recent orders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>