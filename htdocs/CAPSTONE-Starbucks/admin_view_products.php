<?php
session_start();
require_once 'db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle product status updates
if (isset($_POST['update_product_status'])) {
    $product_id = intval($_POST['product_id']);
    $new_status = $_POST['new_status'];
    
    // Validate status
    $valid_statuses = ['active', 'inactive', 'out_of_stock'];
    if (in_array($new_status, $valid_statuses)) {
        $update_query = "UPDATE products SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $new_status, $product_id);
        mysqli_stmt_execute($stmt);
        
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $success_message = "Product status updated successfully.";
        } else {
            $error_message = "Failed to update product status.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle product deletion
if (isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);
    
    // Check if product has orders
    $check_orders_query = "SELECT COUNT(*) as order_count FROM order_items WHERE product_id = ?";
    $stmt = mysqli_prepare($connection, $check_orders_query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($order_data['order_count'] > 0) {
        $error_message = "Cannot delete product. It has been ordered " . $order_data['order_count'] . " time(s).";
    } else {
        // Delete product
        $delete_query = "DELETE FROM products WHERE id = ?";
        $stmt = mysqli_prepare($connection, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Product deleted successfully.";
        } else {
            $error_message = "Failed to delete product.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = array();
$params = array();
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

if (!empty($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
    $param_types .= 'i';
}

if (!empty($status_filter)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Validate sort parameters
$allowed_sort_fields = ['name', 'price', 'stock_quantity', 'created_at', 'category_name'];
$allowed_orders = ['ASC', 'DESC'];

if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'created_at';
}
if (!in_array($sort_order, $allowed_orders)) {
    $sort_order = 'DESC';
}

// Get products with category names and sales data
$products_query = "
    SELECT 
        p.*,
        c.name as category_name,
        COALESCE(SUM(oi.quantity), 0) as total_sold,
        COALESCE(SUM(oi.total_price), 0) as total_revenue,
        COUNT(DISTINCT oi.order_id) as order_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
    $where_clause
    GROUP BY p.id
    ORDER BY $sort_by $sort_order
    LIMIT $per_page OFFSET $offset
";

$stmt = mysqli_prepare($connection, $products_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$products_result = mysqli_stmt_get_result($stmt);

// Get total count for pagination
$count_query = "
    SELECT COUNT(DISTINCT p.id) as total
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    $where_clause
";

$count_stmt = mysqli_prepare($connection, $count_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_products = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_products / $per_page);

// Get categories for filter
$categories_query = "SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC";
$categories_result = mysqli_query($connection, $categories_query);
$categories = array();
if ($categories_result) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row;
    }
}

// Get product statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_products,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_products,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_products,
        COUNT(CASE WHEN status = 'out_of_stock' THEN 1 END) as out_of_stock_products,
        COUNT(CASE WHEN stock_quantity <= min_stock_level THEN 1 END) as low_stock_products,
        COUNT(CASE WHEN is_featured = 1 THEN 1 END) as featured_products,
        AVG(price) as avg_price,
        SUM(stock_quantity) as total_stock_value
    FROM products
";
$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Products - Starbucks Management System</title>
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

        .stat-card.out-of-stock { border-left-color: #dc3545; }
        .stat-card.out-of-stock .value { color: #dc3545; }

        .stat-card.low-stock { border-left-color: #ffc107; }
        .stat-card.low-stock .value { color: #ffc107; }

        .stat-card.featured { border-left-color: #17a2b8; }
        .stat-card.featured .value { color: #17a2b8; }

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

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
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

        .status-out_of_stock {
            background: #fff3cd;
            color: #856404;
        }

        .product-name {
            font-weight: bold;
            color: #00704a;
        }

        .product-sku {
            color: #666;
            font-size: 0.8rem;
        }

        .product-price {
            font-weight: bold;
            color: #28a745;
        }

        .product-stock {
            font-weight: bold;
        }

        .stock-ok {
            color: #28a745;
        }

        .stock-low {
            color: #ffc107;
        }

        .stock-out {
            color: #dc3545;
        }

        .category-badge {
            background: #e3f2fd;
            color: #1565c0;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-size: 0.8rem;
        }

        .featured-badge {
            background: #d1ecf1;
            color: #0c5460;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-size: 0.8rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            text-decoration: none;
            color: #00704a;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: #00704a;
            color: white;
        }

        .pagination .current {
            background: #00704a;
            color: white;
            border-color: #00704a;
        }

        .pagination .disabled {
            color: #6c757d;
            cursor: not-allowed;
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }

        .no-image {
            width: 50px;
            height: 50px;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 0.8rem;
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
                <p>Product Management</p>
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
            <a href="admin_view_products.php" class="nav-item active">
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

        <!-- Product Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="value"><?php echo number_format($stats['total_products']); ?></div>
            </div>
            <div class="stat-card active">
                <h3>Active Products</h3>
                <div class="value"><?php echo number_format($stats['active_products']); ?></div>
            </div>
            <div class="stat-card inactive">
                <h3>Inactive Products</h3>
                <div class="value"><?php echo number_format($stats['inactive_products']); ?></div>
            </div>
            <div class="stat-card out-of-stock">
                <h3>Out of Stock</h3>
                <div class="value"><?php echo number_format($stats['out_of_stock_products']); ?></div>
            </div>
            <div class="stat-card low-stock">
                <h3>Low Stock</h3>
                <div class="value"><?php echo number_format($stats['low_stock_products']); ?></div>
            </div>
            <div class="stat-card featured">
                <h3>Featured Products</h3>
                <div class="value"><?php echo number_format($stats['featured_products']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Average Price</h3>
                <div class="value">₱<?php echo number_format($stats['avg_price'], 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Stock Value</h3>
                <div class="value"><?php echo number_format($stats['total_stock_value']); ?></div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="section">
            <h2>🔍 Search & Filter Products</h2>
            <form method="GET" class="filters">
                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" name="search" id="search" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Name, description, or SKU">
                </div>
                
                <div class="filter-group">
                    <label for="category">Category</label>
                    <select name="category" id="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="out_of_stock" <?php echo $status_filter === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort">Sort By</label>
                    <select name="sort" id="sort">
                        <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Date Added</option>
                        <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Product Name</option>
                        <option value="price" <?php echo $sort_by === 'price' ? 'selected' : ''; ?>>Price</option>
                        <option value="stock_quantity" <?php echo $sort_by === 'stock_quantity' ? 'selected' : ''; ?>>Stock Quantity</option>
                        <option value="category_name" <?php echo $sort_by === 'category_name' ? 'selected' : ''; ?>>Category</option>
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
                    <a href="admin_view_products.php" class="btn btn-secondary">🔄 Reset</a>
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <div class="section">
            <h2>📋 Products List (<?php echo number_format($total_products); ?> total)</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th onclick="sortTable('name')">
                                Product
                                <span class="sort-indicator"><?php echo $sort_by === 'name' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></span>
                            </th>
                            <th onclick="sortTable('category_name')">
                                Category
                                <span class="sort-indicator"><?php echo $sort_by === 'category_name' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></span>
                            </th>
                            <th onclick="sortTable('price')">
                                Price
                                <span class="sort-indicator"><?php echo $sort_by === 'price' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></span>
                            </th>
                            <th onclick="sortTable('stock_quantity')">
                                Stock
                                <span class="sort-indicator"><?php echo $sort_by === 'stock_quantity' ? ($sort_order === 'ASC' ? '↑' : '↓') : ''; ?></span>
                            </th>
                            <th>Sales</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <?php if ($product['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="product-image">
                                    <?php else: ?>
                                        <div class="no-image">No Image</div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <?php if ($product['sku']): ?>
                                            <div class="product-sku">SKU: <?php echo htmlspecialchars($product['sku']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($product['is_featured']): ?>
                                            <span class="featured-badge">⭐ Featured</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="category-badge"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            </td>
                            <td>
                                <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                                <?php if ($product['cost_price'] > 0): ?>
                                    <div style="color: #666; font-size: 0.8rem;">
                                        Cost: ₱<?php echo number_format($product['cost_price'], 2); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="product-stock <?php 
                                    echo $product['stock_quantity'] == 0 ? 'stock-out' : 
                                        ($product['stock_quantity'] <= $product['min_stock_level'] ? 'stock-low' : 'stock-ok'); 
                                ?>">
                                    <?php echo number_format($product['stock_quantity']); ?>
                                </div>
                                <div style="color: #666; font-size: 0.8rem;">
                                    Min: <?php echo number_format($product['min_stock_level']); ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: bold; color: #00704a;">
                                    <?php echo number_format($product['total_sold']); ?> sold
                                </div>
                                <div style="color: #666; font-size: 0.8rem;">
                                    ₱<?php echo number_format($product['total_revenue'], 2); ?> revenue
                                </div>
                                <div style="color: #666; font-size: 0.8rem;">
                                    <?php echo number_format($product['order_count']); ?> orders
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $product['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <select name="new_status" onchange="this.form.submit()" class="btn btn-sm">
                                            <option value="">Change Status</option>
                                            <option value="active" <?php echo $product['status'] === 'active' ? 'disabled' : ''; ?>>Activate</option>
                                            <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'disabled' : ''; ?>>Deactivate</option>
                                            <option value="out_of_stock" <?php echo $product['status'] === 'out_of_stock' ? 'disabled' : ''; ?>>Out of Stock</option>
                                        </select>
                                        <input type="hidden" name="update_product_status" value="1">
                                    </form>
                                    
                                    <a href="admin_add_stock.php?product_id=<?php echo $product['id']; ?>" 
                                       class="btn btn-warning btn-sm">📦 Add Stock</a>
                                    
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="delete_product" class="btn btn-danger btn-sm">🗑️ Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">« Previous</a>
                <?php else: ?>
                    <span class="disabled">« Previous</span>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next »</a>
                <?php else: ?>
                    <span class="disabled">Next »</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
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
            // Alt + P to focus on search
            if (e.altKey && e.key === 'p') {
                e.preventDefault();
                document.getElementById('search').focus();
            }
            
            // Alt + F to focus on filter
            if (e.altKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('category').focus();
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
                if (this.value && !confirm('Are you sure you want to change this product\'s status?')) {
                    this.value = '';
                    return false;
                }
            });
        });
    </script>
</body>
</html>
