<?php
require_once 'db_connect.php';
session_start();

// Check if user is logged in as customer
if (!isset($_SESSION['customer_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: customer_login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

// Get customer's orders
try {
    $orders_stmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.order_item_id) as item_count, SUM(oi.quantity * oi.price) as total_amount
        FROM orders o 
        LEFT JOIN order_items oi ON o.order_id = oi.order_id 
        WHERE o.customer_id = ? 
        GROUP BY o.order_id 
        ORDER BY o.created_at DESC 
        LIMIT 10
    ");
    $orders_stmt->execute([$customer_id]);
    $recent_orders = $orders_stmt->fetchAll();
} catch (PDOException $e) {
    $recent_orders = [];
}

// Get total orders count
try {
    $total_orders_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ?");
    $total_orders_stmt->execute([$customer_id]);
    $total_orders = $total_orders_stmt->fetch()['total'];
} catch (PDOException $e) {
    $total_orders = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Chowking Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }
        .dashboard-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .order-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-2px);
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            font-weight: 600;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .welcome-section {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-utensils me-2"></i>Chowking
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($customer_name); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="customer_place_order.php">
                            <i class="fas fa-shopping-cart me-2"></i>Place Order
                        </a></li>
                        <li><a class="dropdown-item" href="customer_product.php">
                            <i class="fas fa-utensils me-2"></i>View Products
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">Welcome back, <?php echo htmlspecialchars($customer_name); ?>!</h1>
                    <p class="mb-0">Manage your orders and explore our delicious menu</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="customer_place_order.php" class="btn btn-light btn-lg">
                        <i class="fas fa-plus me-2"></i>Place New Order
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                    <h3><?php echo $total_orders; ?></h3>
                    <p class="mb-0">Total Orders</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <i class="fas fa-utensils fa-3x mb-3"></i>
                    <h3>50+</h3>
                    <p class="mb-0">Available Products</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <i class="fas fa-star fa-3x mb-3"></i>
                    <h3>4.8</h3>
                    <p class="mb-0">Average Rating</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-container p-4 mb-4">
            <h4 class="mb-3">
                <i class="fas fa-bolt me-2"></i>Quick Actions
            </h4>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <a href="customer_place_order.php" class="btn btn-primary w-100 py-3">
                        <i class="fas fa-plus-circle fa-2x mb-2 d-block"></i>
                        Place Order
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="customer_product.php" class="btn btn-outline-primary w-100 py-3">
                        <i class="fas fa-utensils fa-2x mb-2 d-block"></i>
                        View Menu
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="#orders" class="btn btn-outline-primary w-100 py-3">
                        <i class="fas fa-history fa-2x mb-2 d-block"></i>
                        Order History
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="profile.php" class="btn btn-outline-primary w-100 py-3">
                        <i class="fas fa-user fa-2x mb-2 d-block"></i>
                        My Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="dashboard-container p-4" id="orders">
            <h4 class="mb-3">
                <i class="fas fa-clock me-2"></i>Recent Orders
            </h4>
            <?php if (empty($recent_orders)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No orders yet</h5>
                    <p class="text-muted">Start by placing your first order!</p>
                    <a href="customer_place_order.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Place Order
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($recent_orders as $order): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card order-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">Order #<?php echo $order['order_id']; ?></h6>
                                        <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </div>
                                    <p class="card-text text-muted small mb-2">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                                    </p>
                                    <p class="card-text mb-2">
                                        <i class="fas fa-box me-1"></i>
                                        <?php echo $order['item_count']; ?> item(s)
                                    </p>
                                    <p class="card-text fw-bold text-primary">
                                        <i class="fas fa-dollar-sign me-1"></i>
                                        ₱<?php echo number_format($order['total_amount'], 2); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="order_history.php" class="btn btn-outline-primary">
                        <i class="fas fa-history me-2"></i>View All Orders
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
