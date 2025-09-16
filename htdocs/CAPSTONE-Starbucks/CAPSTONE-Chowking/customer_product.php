<?php
require_once 'db_connect.php';
session_start();

// Check if user is logged in as customer
if (!isset($_SESSION['customer_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: customer_login.php');
    exit();
}

$customer_name = $_SESSION['customer_name'];

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'name';

// Build query
$where_conditions = ["is_available = 1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(product_name LIKE ? OR product_description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $where_conditions[] = "product_category = ?";
    $params[] = $category;
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$sort_options = [
    'name' => 'product_name ASC',
    'price_low' => 'product_price ASC',
    'price_high' => 'product_price DESC',
    'category' => 'product_category ASC'
];

$order_by = $sort_options[$sort] ?? 'product_name ASC';

try {
    $products_stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE $where_clause 
        ORDER BY $order_by
    ");
    $products_stmt->execute($params);
    $products = $products_stmt->fetchAll();
    
    // Get categories for filter
    $categories_stmt = $pdo->query("SELECT DISTINCT product_category FROM products WHERE is_available = 1 ORDER BY product_category");
    $categories = $categories_stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Menu - Chowking Management System</title>
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
        .product-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 15px 15px 0 0;
        }
        .product-price {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .category-badge {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .filter-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .logo {
            color: #667eea;
            font-size: 2rem;
            font-weight: bold;
        }
        .no-products {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="customer_dashboard.php">
                <i class="fas fa-utensils me-2"></i>Chowking
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($customer_name); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="customer_dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a></li>
                        <li><a class="dropdown-item" href="customer_place_order.php">
                            <i class="fas fa-shopping-cart me-2"></i>Place Order
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
        <!-- Header -->
        <div class="text-center mb-4">
            <h1 class="logo mb-2">
                <i class="fas fa-utensils me-2"></i>Our Delicious Menu
            </h1>
            <p class="text-white">Discover our amazing selection of Filipino-Chinese cuisine</p>
        </div>

        <!-- Filters -->
        <div class="filter-container p-4 mb-4">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">
                            <i class="fas fa-search me-2"></i>Search Products
                        </label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by name or description...">
                    </div>
                    <div class="col-md-3">
                        <label for="category" class="form-label">
                            <i class="fas fa-tags me-2"></i>Category
                        </label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['product_category']); ?>" 
                                        <?php echo ($category === $cat['product_category']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['product_category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="sort" class="form-label">
                            <i class="fas fa-sort me-2"></i>Sort By
                        </label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="name" <?php echo ($sort === 'name') ? 'selected' : ''; ?>>Name A-Z</option>
                            <option value="price_low" <?php echo ($sort === 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo ($sort === 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="category" <?php echo ($sort === 'category') ? 'selected' : ''; ?>>Category</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
            <div class="no-products text-center py-5">
                <i class="fas fa-utensils fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No products found</h4>
                <p class="text-muted">Try adjusting your search or filter criteria.</p>
                <a href="customer_product.php" class="btn btn-primary">
                    <i class="fas fa-refresh me-2"></i>Clear Filters
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($products as $product): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="product-card h-100">
                            <?php if (!empty($product['product_image']) && file_exists($product['product_image'])): ?>
                                <img src="<?php echo htmlspecialchars($product['product_image']); ?>" 
                                     class="product-image" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            <?php else: ?>
                                <div class="product-image d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-utensils fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                    <span class="category-badge"><?php echo htmlspecialchars($product['product_category']); ?></span>
                                </div>
                                
                                <p class="card-text text-muted mb-3">
                                    <?php echo htmlspecialchars(substr($product['product_description'], 0, 100)); ?>
                                    <?php if (strlen($product['product_description']) > 100): ?>...<?php endif; ?>
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="product-price">
                                        ₱<?php echo number_format($product['product_price'], 2); ?>
                                    </div>
                                    <a href="customer_place_order.php?product_id=<?php echo $product['product_id']; ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-shopping-cart me-2"></i>Order Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Back to Dashboard -->
        <div class="text-center mt-4">
            <a href="customer_dashboard.php" class="btn btn-outline-light btn-lg">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
