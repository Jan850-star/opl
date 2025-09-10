<?php
session_start();
require_once 'db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle add product
if (isset($_POST['add_product_submit'])) {
    $name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $min_stock_level = intval($_POST['min_stock_level']);
    $status = $_POST['status'];
    
    // Validation
    if (empty($name) || empty($description) || $price <= 0 || $stock_quantity < 0 || $min_stock_level < 0 || $category_id <= 0) {
        $error_message = "Please fill in all required fields with valid values.";
    } else {
        // Check if product name already exists
        $check_query = "SELECT id FROM products WHERE name = ?";
        $stmt = mysqli_prepare($connection, $check_query);
        mysqli_stmt_bind_param($stmt, "s", $name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error_message = "A product with this name already exists.";
        } else {
            // Insert new product
            $insert_query = "INSERT INTO products (category_id, name, description, price, stock_quantity, min_stock_level, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = mysqli_prepare($connection, $insert_query);
            mysqli_stmt_bind_param($stmt, "issdiis", $category_id, $name, $description, $price, $stock_quantity, $min_stock_level, $status);
            
            if (mysqli_stmt_execute($stmt)) {
                $product_id = mysqli_insert_id($connection);
                
                // Log inventory transaction if initial stock > 0
                if ($stock_quantity > 0) {
                    $log_query = "INSERT INTO inventory_transactions (product_id, transaction_type, quantity, previous_quantity, new_quantity, reference_type, notes, created_by) VALUES (?, 'stock_in', ?, 0, ?, 'initial_stock', ?, ?)";
                    $log_stmt = mysqli_prepare($connection, $log_query);
                    $notes = "Initial stock for new product";
                    $admin_id = $_SESSION['admin_id'];
                    mysqli_stmt_bind_param($log_stmt, "iiisi", $product_id, $stock_quantity, $stock_quantity, $notes, $admin_id);
                    mysqli_stmt_execute($log_stmt);
                    mysqli_stmt_close($log_stmt);
                }
                
                $success_message = "Product '$name' has been successfully added with ID #$product_id.";
                
                // Clear form data
                $_POST = array();
            } else {
                $error_message = "Failed to add product: " . mysqli_error($connection);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get all products for display with category names
$products_query = "SELECT p.id, p.name, p.description, p.price, c.name as category_name, p.stock_quantity, p.min_stock_level, p.status, p.created_at 
                   FROM products p 
                   JOIN categories c ON p.category_id = c.id 
                   ORDER BY p.created_at DESC LIMIT 10";
$products_result = mysqli_query($connection, $products_query);

// Get all categories for the dropdown
$categories_query = "SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC";
$categories_result = mysqli_query($connection, $categories_query);
$categories = array();
if ($categories_result) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Starbucks Management System</title>
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

        .add-product-section {
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            border: 2px solid #2196f3;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.2);
        }

        .add-product-title {
            color: #1565c0;
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: bold;
            color: #1565c0;
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.75rem;
            border: 2px solid #bbdefb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2196f3;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .submit-btn {
            background: linear-gradient(135deg, #2196f3, #42a5f5);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: all 0.3s;
            grid-column: 1 / -1;
            justify-self: center;
            min-width: 200px;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #1976d2, #1e88e5);
            transform: translateY(-2px);
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

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .price {
            font-weight: bold;
            color: #00704a;
        }

        .category-badge {
            background: #e3f2fd;
            color: #1565c0;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
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

            .form-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
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
            <a href="admin_add_product.php" class="nav-item active">
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

        <!-- Add Product Section -->
        <div class="add-product-section">
            <div class="add-product-title">➕ Add New Product</div>
            <form method="POST" class="form-grid" onsubmit="return validateForm();">
                <div class="form-group">
                    <label for="product_name">Product Name *</label>
                    <input type="text" name="product_name" id="product_name" required 
                           value="<?php echo isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : ''; ?>"
                           placeholder="Enter product name">
                </div>

                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select name="category_id" id="category_id" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="price">Price (₱) *</label>
                    <input type="number" name="price" id="price" step="0.01" min="0" required 
                           value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>"
                           placeholder="0.00">
                </div>

                <div class="form-group">
                    <label for="stock_quantity">Initial Stock *</label>
                    <input type="number" name="stock_quantity" id="stock_quantity" min="0" required 
                           value="<?php echo isset($_POST['stock_quantity']) ? htmlspecialchars($_POST['stock_quantity']) : '0'; ?>"
                           placeholder="0">
                </div>

                <div class="form-group">
                    <label for="min_stock_level">Minimum Stock Level *</label>
                    <input type="number" name="min_stock_level" id="min_stock_level" min="0" required 
                           value="<?php echo isset($_POST['min_stock_level']) ? htmlspecialchars($_POST['min_stock_level']) : '5'; ?>"
                           placeholder="5">
                </div>

                <div class="form-group">
                    <label for="status">Status *</label>
                    <select name="status" id="status" required>
                        <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="description">Description *</label>
                    <textarea name="description" id="description" required 
                              placeholder="Enter product description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <button type="submit" name="add_product_submit" class="submit-btn">
                    ➕ Add Product
                </button>
            </form>
        </div>

        <!-- Recent Products Section -->
        <div class="section">
            <h2>📋 Recent Products Added</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Added Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo $product['id']; ?></strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                <br>
                                <small style="color: #666;"><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . (strlen($product['description']) > 50 ? '...' : ''); ?></small>
                            </td>
                            <td>
                                <span class="category-badge"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            </td>
                            <td>
                                <span class="price">₱<?php echo number_format($product['price'], 2); ?></span>
                            </td>
                            <td>
                                <?php echo number_format($product['stock_quantity']); ?>
                                <br>
                                <small style="color: #666;">Min: <?php echo number_format($product['min_stock_level']); ?></small>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($product['created_at'])); ?>
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

        // Form validation
        function validateForm() {
            const productName = document.getElementById('product_name').value.trim();
            const description = document.getElementById('description').value.trim();
            const price = parseFloat(document.getElementById('price').value);
            const stockQuantity = parseInt(document.getElementById('stock_quantity').value);
            const minStockLevel = parseInt(document.getElementById('min_stock_level').value);
            const categoryId = document.getElementById('category_id').value;
            const status = document.getElementById('status').value;

            if (!productName) {
                alert('Please enter a product name.');
                document.getElementById('product_name').focus();
                return false;
            }

            if (!description) {
                alert('Please enter a product description.');
                document.getElementById('description').focus();
                return false;
            }

            if (price <= 0) {
                alert('Please enter a valid price greater than 0.');
                document.getElementById('price').focus();
                return false;
            }

            if (stockQuantity < 0) {
                alert('Stock quantity cannot be negative.');
                document.getElementById('stock_quantity').focus();
                return false;
            }

            if (minStockLevel < 0) {
                alert('Minimum stock level cannot be negative.');
                document.getElementById('min_stock_level').focus();
                return false;
            }

            if (!categoryId) {
                alert('Please select a category.');
                document.getElementById('category_id').focus();
                return false;
            }

            if (!status) {
                alert('Please select a status.');
                document.getElementById('status').focus();
                return false;
            }

            return confirm('Are you sure you want to add this product?');
        }

        // Real-time price formatting
        document.getElementById('price').addEventListener('input', function() {
            let value = this.value;
            if (value && !isNaN(value)) {
                this.value = parseFloat(value).toFixed(2);
            }
        });

        // Auto-suggest minimum stock level based on initial stock
        document.getElementById('stock_quantity').addEventListener('input', function() {
            const stockQuantity = parseInt(this.value);
            const minStockInput = document.getElementById('min_stock_level');
            
            if (stockQuantity > 0 && minStockInput.value == '') {
                minStockInput.value = Math.max(5, Math.floor(stockQuantity * 0.2));
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + P to focus on product name
            if (e.altKey && e.key === 'p') {
                e.preventDefault();
                document.getElementById('product_name').focus();
            }
            
            // Alt + V to go to View Products
            if (e.altKey && e.key === 'v') {
                e.preventDefault();
                window.location.href = 'admin_view_products.php';
            }
            
            // Alt + S to go to Add Stock
            if (e.altKey && e.key === 's') {
                e.preventDefault();
                window.location.href = 'admin_add_stock.php';
            }
            
            // Alt + C to go to View Customers
            if (e.altKey && e.key === 'c') {
                e.preventDefault();
                window.location.href = 'admin_view_customers.php';
            }
            
            // Esc to clear form
            if (e.key === 'Escape') {
                if (confirm('Are you sure you want to clear the form?')) {
                    document.querySelector('.form-grid').reset();
                }
            }
        });

        // Form submission enhancement
        document.querySelector('.form-grid').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Adding Product...';
            submitBtn.disabled = true;
            
            // Re-enable button after 5 seconds if form doesn't submit
            setTimeout(function() {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });

        // Add visual feedback for form interactions
        document.querySelectorAll('input, select, textarea').forEach(function(element) {
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
    </script>
</body>
</html>
