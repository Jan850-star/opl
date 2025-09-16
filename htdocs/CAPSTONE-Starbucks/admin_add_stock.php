<?php
session_start();

// Database connection
require_once 'db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity_to_add = intval($_POST['quantity']);
    $notes = trim($_POST['notes']);
    
    if ($product_id > 0 && $quantity_to_add > 0) {
        try {
            $pdo->beginTransaction();
            
            // Get current product stock
            $stmt = $pdo->prepare("SELECT name, stock_quantity FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                throw new Exception("Product not found");
            }
            
            $previous_quantity = $product['stock_quantity'];
            $new_quantity = $previous_quantity + $quantity_to_add;
            
            // Update product stock
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_quantity, $product_id]);
            
            // Record inventory transaction
            $stmt = $pdo->prepare("
                INSERT INTO inventory_transactions 
                (product_id, transaction_type, quantity, previous_quantity, new_quantity, reference_type, notes, created_by, created_at) 
                VALUES (?, 'stock_in', ?, ?, ?, 'adjustment', ?, ?, NOW())
            ");
            $stmt->execute([$product_id, $quantity_to_add, $previous_quantity, $new_quantity, $notes, $admin_id]);
            
            $pdo->commit();
            $message = "Successfully added {$quantity_to_add} units to {$product['name']}. New stock: {$new_quantity}";
            
        } catch (Exception $e) {
            $pdo->rollback();
            $error = "Error adding stock: " . $e->getMessage();
        }
    } else {
        $error = "Please select a valid product and enter a positive quantity.";
    }
}

// Get all products for dropdown
$query = "
    SELECT p.id, p.name, p.stock_quantity, p.min_stock_level, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' 
    ORDER BY c.name, p.name
";
$stmt = $pdo->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent stock transactions
$query = "
    SELECT it.*, p.name as product_name, a.first_name, a.last_name 
    FROM inventory_transactions it 
    JOIN products p ON it.product_id = p.id 
    LEFT JOIN admins a ON it.created_by = a.id 
    WHERE it.transaction_type = 'stock_in' 
    ORDER BY it.created_at DESC 
    LIMIT 10
";
$stmt = $pdo->prepare($query);
$stmt->execute();
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Stock - Starbucks Admin Panel</title>
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

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            outline: none;
            border-color: #00704a;
            box-shadow: 0 0 0 3px rgba(0, 112, 74, 0.1);
            transform: translateY(-2px);
        }

        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.9);
            cursor: pointer;
        }

        .form-select:focus {
            outline: none;
            border-color: #00704a;
            box-shadow: 0 0 0 3px rgba(0, 112, 74, 0.1);
            transform: translateY(-2px);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00704a, #28a745);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 112, 74, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #005a3c, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 112, 74, 0.4);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        thead tr {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #00704a;
        }

        .low-stock {
            background-color: #fff3cd !important;
            color: #856404;
        }

        .out-of-stock {
            background-color: #f8d7da !important;
            color: #721c24;
        }

        .good-stock {
            background-color: #d4edda !important;
            color: #155724;
        }

        .badge {
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .form-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        .stock-info {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: rgba(0, 112, 74, 0.1);
            border-radius: 5px;
            font-size: 0.875rem;
            color: #00704a;
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

            .container {
                padding: 1rem;
            }

            .section {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>☕ Starbucks Admin</h1>
                <p>Stock Management</p>
            </div>
            <div class="admin-info">
                <span>Welcome, <?php echo isset($_SESSION['admin_name']) ? htmlspecialchars($_SESSION['admin_name']) : 'Admin'; ?></span>
                <a href="admin_logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <!-- Navigation Menu -->
    <nav class="nav-menu">
        <div class="nav-content">
            <a href="admin_dashboard.php" class="nav-item">
                🏠 Dashboard
            </a>
            <a href="admin_add_stock.php" class="nav-item active">
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
        <?php if ($message): ?>
        <div class="success-message">
            ✅ <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="error-message">
            ❌ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Add Stock Form -->
            <div class="col-md-6">
                <div class="section">
                    <h2>📦 Add Stock to Product</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="product_id" class="form-label">Select Product</label>
                            <select class="form-select" id="product_id" name="product_id" required>
                                <option value="">Choose a product...</option>
                                <?php 
                                $current_category = '';
                                foreach ($products as $product): 
                                    if ($current_category != $product['category_name']): 
                                        if ($current_category != '') echo '</optgroup>';
                                        echo '<optgroup label="' . htmlspecialchars($product['category_name']) . '">';
                                        $current_category = $product['category_name'];
                                    endif;
                                    
                                    $stock_status = '';
                                    if ($product['stock_quantity'] == 0) {
                                        $stock_status = ' (OUT OF STOCK)';
                                    } elseif ($product['stock_quantity'] <= $product['min_stock_level']) {
                                        $stock_status = ' (LOW STOCK)';
                                    }
                                ?>
                                    <option value="<?php echo $product['id']; ?>" 
                                            data-current-stock="<?php echo $product['stock_quantity']; ?>"
                                            data-min-stock="<?php echo $product['min_stock_level']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?> 
                                        (Current: <?php echo $product['stock_quantity']; ?>)
                                        <?php echo $stock_status; ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if ($current_category != '') echo '</optgroup>'; ?>
                            </select>
                            <div id="stock-info" class="stock-info" style="display: none;"></div>
                        </div>

                        <div class="form-group">
                            <label for="quantity" class="form-label">Quantity to Add</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                            <div class="form-text">Enter the number of units to add to stock</div>
                        </div>

                        <div class="form-group">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Add any notes about this stock addition..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            ➕ Add Stock
                        </button>
                    </form>
                </div>
            </div>

            <!-- Product Stock Overview -->
            <div class="col-md-6">
                <div class="section">
                    <h2>📊 Stock Overview</h2>
                    <div class="table-container" style="max-height: 400px; overflow-y: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Current</th>
                                    <th>Min Level</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): 
                                    $row_class = '';
                                    $status = '';
                                    if ($product['stock_quantity'] == 0) {
                                        $row_class = 'out-of-stock';
                                        $status = '<span class="badge badge-danger">Out of Stock</span>';
                                    } elseif ($product['stock_quantity'] <= $product['min_stock_level']) {
                                        $row_class = 'low-stock';
                                        $status = '<span class="badge badge-warning">Low Stock</span>';
                                    } else {
                                        $row_class = 'good-stock';
                                        $status = '<span class="badge badge-success">Good</span>';
                                    }
                                ?>
                                    <tr class="<?php echo $row_class; ?>">
                                        <td>
                                            <small><?php echo htmlspecialchars($product['name']); ?></small>
                                        </td>
                                        <td><?php echo $product['stock_quantity']; ?></td>
                                        <td><?php echo $product['min_stock_level']; ?></td>
                                        <td><?php echo $status; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="section">
            <h2>📋 Recent Stock Additions</h2>
            <?php if (empty($recent_transactions)): ?>
                <p style="color: #6c757d; text-align: center; padding: 2rem;">No recent stock additions found.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Product</th>
                                <th>Quantity Added</th>
                                <th>Previous Stock</th>
                                <th>New Stock</th>
                                <th>Added By</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['product_name']); ?></td>
                                    <td><span class="badge badge-success">+<?php echo $transaction['quantity']; ?></span></td>
                                    <td><?php echo $transaction['previous_quantity']; ?></td>
                                    <td><?php echo $transaction['new_quantity']; ?></td>
                                    <td>
                                        <?php 
                                        if ($transaction['first_name']) {
                                            echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']);
                                        } else {
                                            echo 'System';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        echo $transaction['notes'] ? htmlspecialchars($transaction['notes']) : '<em style="color: #6c757d;">No notes</em>';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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

        // Update stock info when product is selected
        document.getElementById('product_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const currentStock = selectedOption.dataset.currentStock;
            const minStock = selectedOption.dataset.minStock;
            
            if (currentStock !== undefined) {
                const stockInfo = document.getElementById('stock-info');
                if (stockInfo) {
                    stockInfo.style.display = 'block';
                    stockInfo.innerHTML = `Current Stock: ${currentStock} | Minimum Level: ${minStock}`;
                }
            } else {
                const stockInfo = document.getElementById('stock-info');
                if (stockInfo) {
                    stockInfo.style.display = 'none';
                }
            }
        });

        // Add visual feedback for form interactions
        document.querySelectorAll('select, input, textarea').forEach(function(element) {
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

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + D for Dashboard
            if (e.altKey && e.key === 'd') {
                e.preventDefault();
                window.location.href = 'admin_dashboard.php';
            }
            
            // Alt + P for Add Product
            if (e.altKey && e.key === 'p') {
                e.preventDefault();
                window.location.href = 'admin_add_product.php';
            }
            
            // Alt + V for View Products
            if (e.altKey && e.key === 'v') {
                e.preventDefault();
                window.location.href = 'admin_view_products.php';
            }
            
            // Alt + C for View Customers
            if (e.altKey && e.key === 'c') {
                e.preventDefault();
                window.location.href = 'admin_view_customers.php';
            }
        });

        // Add loading animation for form submission
        document.querySelector('form').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.style.opacity = '0.7';
            submitBtn.style.transform = 'scale(0.98)';
            submitBtn.innerHTML = '⏳ Adding Stock...';
        });

        // Add hover effects to table rows
        document.querySelectorAll('tbody tr').forEach(function(row) {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(0, 112, 74, 0.05)';
                this.style.transition = 'background-color 0.2s';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });

        // Initialize tooltips for badges
        document.querySelectorAll('.badge').forEach(function(badge) {
            badge.title = 'Stock status indicator';
        });
    </script>
</body>
</html>