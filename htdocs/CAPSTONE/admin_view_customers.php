<?php
session_start();
require_once 'db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle customer status updates
if (isset($_POST['update_customer_status'])) {
    $customer_id = intval($_POST['customer_id']);
    $new_status = $_POST['new_status'];
    
    // Validate status
    $valid_statuses = ['active', 'inactive', 'suspended'];
    if (in_array($new_status, $valid_statuses)) {
        $update_query = "UPDATE customers SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $new_status, $customer_id);
        mysqli_stmt_execute($stmt);
        
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $success_message = "Customer status updated successfully.";
        } else {
            $error_message = "Failed to update customer status.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Build query
$where_conditions = array();
$params = array();
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ssss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Validate sort parameters
$allowed_sort_fields = ['first_name', 'last_name', 'email', 'created_at', 'total_orders', 'total_spent'];
$allowed_orders = ['ASC', 'DESC'];

if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'created_at';
}
if (!in_array($sort_order, $allowed_orders)) {
    $sort_order = 'DESC';
}

// Get customers with order statistics
$customers_query = "
    SELECT 
        c.*,
        COUNT(o.id) as total_orders,
        COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.final_amount ELSE 0 END), 0) as total_spent,
        MAX(o.created_at) as last_order_date
    FROM customers c
    LEFT JOIN orders o ON c.id = o.customer_id
    $where_clause
    GROUP BY c.id
    ORDER BY $sort_by $sort_order
    LIMIT 50
";

$stmt = mysqli_prepare($connection, $customers_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$customers_result = mysqli_stmt_get_result($stmt);

// Get customer statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_customers,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_customers,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_customers,
        COUNT(CASE WHEN status = 'suspended' THEN 1 END) as suspended_customers,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today,
        COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as new_this_week
    FROM customers
";
$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Customers - Starbucks Management System</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        .stat-card.active { border-left-color: #28a745; }
        .stat-card.active .value { color: #28a745; }

        .stat-card.inactive { border-left-color: #6c757d; }
        .stat-card.inactive .value { color: #6c757d; }

        .stat-card.suspended { border-left-color: #dc3545; }
        .stat-card.suspended .value { color: #dc3545; }

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

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: bold;
            color: #00704a;
            margin-bottom: 0.5rem;
        }

        .filter-group input,
        .filter-group select {
            padding: 0.75rem;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #00704a;
        }

        .filter-buttons {
            display: flex;
            gap: 1rem;
            align-items: end;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00704a, #28a745);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #005a3c, #1e7e34);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
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
            cursor: pointer;
            user-select: none;
        }

        th:hover {
            background: #e9ecef;
        }

        .sort-indicator {
            margin-left: 0.5rem;
            font-size: 0.8rem;
        }

        .status-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .status-suspended {
            background: #fff3cd;
            color: #856404;
        }

        .customer-name {
            font-weight: bold;
            color: #00704a;
        }

        .customer-email {
            color: #666;
            font-size: 0.9rem;
        }

        .customer-phone {
            color: #666;
            font-size: 0.9rem;
        }

        .total-spent {
            font-weight: bold;
            color: #28a745;
        }

        .total-orders {
            font-weight: bold;
            color: #00704a;
        }

        .last-order {
            color: #666;
            font-size: 0.9rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
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

            .filters {
                grid-template-columns: 1fr;
            }

            .filter-buttons {
                flex-direction: column;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>☕ Starbucks Admin</h1>
                <p>Customer Management</p>
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
            <a href="admin_dashboard.php" class="nav-item">
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
            <a href="admin_view_customers.php" class="nav-item active">
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

        <!-- Customer Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Customers</h3>
                <div class="value"><?php echo number_format($stats['total_customers']); ?></div>
            </div>
            <div class="stat-card active">
                <h3>Active Customers</h3>
                <div class="value"><?php echo number_format($stats['active_customers']); ?></div>
            </div>
            <div class="stat-card inactive">
                <h3>Inactive Customers</h3>
                <div class="value"><?php echo number_format($stats['inactive_customers']); ?></div>
            </div>
            <div class="stat-card suspended">
                <h3>Suspended Customers</h3>
                <div class="value"><?php echo number_format($stats['suspended_customers']); ?></div>
            </div>
            <div class="stat-card">
                <h3>New Today</h3>
                <div class="value"><?php echo number_format($stats['new_today']); ?></div>
            </div>
            <div class="stat-card">
                <h3>New This Week</h3>
                <div class="value"><?php echo number_format($stats['new_this_week']); ?></div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="section">
            <h2>🔍 Search & Filter Customers</h2>
            <form method="GET" class="filters">
                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" name="search" id="search" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Name, email, or phone">
                </div>
                
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort">Sort By</label>
                    <select name="sort" id="sort">
                        <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Registration Date</option>
                        <option value="first_name" <?php echo $sort_by === 'first_name' ? 'selected' : ''; ?>>First Name</option>
                        <option value="last_name" <?php echo $sort_by === 'last_name' ? 'selected' : ''; ?>>Last Name</option>
                        <option value="email" <?php echo $sort_by === 'email' ? 'selected' : ''; ?>>Email</option>
                        <option value="total_orders" <?php echo $sort_by === 'total_orders' ? 'selected' : ''; ?>>Total Orders</option>
                        <option value="total_spent" <?php echo $sort_by === 'total_spent' ? 'selected' : ''; ?>>Total Spent</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="order">Order</label>
                    <select name="order" id="order">
                        <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                        <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                    </select>
                </div>
                
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">🔍 Search</button>
                    <a href="admin_view_customers.php" class="btn btn-secondary">🔄 Reset</a>
                </div>
            </form>
        </div>

        <!-- Customers Table -->
        <div class="section">
            <h2>👥 Customer List</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th onclick="sortTable('first_name')">
                                Customer Name
                                <span class="sort-indicator"><?php echo $sort_by === 'first_name' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></span>
                            </th>
                            <th onclick="sortTable('email')">
                                Contact
                                <span class="sort-indicator"><?php echo $sort_by === 'email' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></span>
                            </th>
                            <th onclick="sortTable('total_orders')">
                                Orders
                                <span class="sort-indicator"><?php echo $sort_by === 'total_orders' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></span>
                            </th>
                            <th onclick="sortTable('total_spent')">
                                Total Spent
                                <span class="sort-indicator"><?php echo $sort_by === 'total_spent' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></span>
                            </th>
                            <th onclick="sortTable('created_at')">
                                Member Since
                                <span class="sort-indicator"><?php echo $sort_by === 'created_at' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></span>
                            </th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($customer = mysqli_fetch_assoc($customers_result)): ?>
                        <tr>
                            <td>
                                <div class="customer-name">
                                    <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                </div>
                                <div style="color: #666; font-size: 0.8rem;">
                                    ID: #<?php echo $customer['id']; ?>
                                </div>
                            </td>
                            <td>
                                <div class="customer-email">
                                    📧 <?php echo htmlspecialchars($customer['email']); ?>
                                </div>
                                <div class="customer-phone">
                                    📞 <?php echo htmlspecialchars($customer['phone']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="total-orders">
                                    <?php echo number_format($customer['total_orders']); ?>
                                </div>
                                <?php if ($customer['last_order_date']): ?>
                                <div class="last-order">
                                    Last: <?php echo date('M j, Y', strtotime($customer['last_order_date'])); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="total-spent">
                                    ₱<?php echo number_format($customer['total_spent'], 2); ?>
                                </div>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($customer['created_at'])); ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $customer['status']; ?>">
                                    <?php echo ucfirst($customer['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                        <select name="new_status" onchange="this.form.submit()" class="btn btn-sm">
                                            <option value="">Change Status</option>
                                            <option value="active" <?php echo $customer['status'] === 'active' ? 'disabled' : ''; ?>>Activate</option>
                                            <option value="inactive" <?php echo $customer['status'] === 'inactive' ? 'disabled' : ''; ?>>Deactivate</option>
                                            <option value="suspended" <?php echo $customer['status'] === 'suspended' ? 'disabled' : ''; ?>>Suspend</option>
                                        </select>
                                        <input type="hidden" name="update_customer_status" value="1">
                                    </form>
                                </div>
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

        // Sort table functionality
        function sortTable(column) {
            const url = new URL(window.location);
            const currentSort = url.searchParams.get('sort');
            const currentOrder = url.searchParams.get('order');
            
            let newOrder = 'ASC';
            if (currentSort === column && currentOrder === 'ASC') {
                newOrder = 'DESC';
            }
            
            url.searchParams.set('sort', column);
            url.searchParams.set('order', newOrder);
            window.location.href = url.toString();
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + C to focus on search
            if (e.altKey && e.key === 'c') {
                e.preventDefault();
                document.getElementById('search').focus();
            }
            
            // Alt + V to go to View Products
            if (e.altKey && e.key === 'v') {
                e.preventDefault();
                window.location.href = 'admin_view_products.php';
            }
            
            // Alt + P to go to Add Product
            if (e.altKey && e.key === 'p') {
                e.preventDefault();
                window.location.href = 'admin_add_product.php';
            }
            
            // Alt + S to go to Add Stock
            if (e.altKey && e.key === 's') {
                e.preventDefault();
                window.location.href = 'admin_add_stock.php';
            }
            
            // Alt + F to focus on filter
            if (e.altKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('status').focus();
            }
        });

        // Add visual feedback for form interactions
        document.querySelectorAll('input, select').forEach(function(element) {
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

        // Add hover effects to table rows
        document.querySelectorAll('tbody tr').forEach(function(row) {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });

        // Status change confirmation
        document.querySelectorAll('select[name="new_status"]').forEach(function(select) {
            select.addEventListener('change', function() {
                if (this.value && !confirm('Are you sure you want to change this customer\'s status?')) {
                    this.value = '';
                    return false;
                }
            });
        });
    </script>
</body>
</html>
