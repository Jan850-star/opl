<?php
session_start();
// Database connection settings
$host = '127.0.0.1';
$db   = 'mariadb';
$user = 'mariadb';
$pass = 'mariadb';
$charset = 'utf8mb4';

// Set up DSN and connect
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo "Database connection failed.";
    exit;
}

// Get selected category from URL parameter
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Fetch active products with stock > 0, filtered by category
if ($selected_category === 'all') {
    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active' AND p.stock_quantity > 0 
        ORDER BY p.is_featured DESC, p.sort_order ASC, p.name ASC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active' AND p.stock_quantity > 0 AND p.category_id = ? 
        ORDER BY p.is_featured DESC, p.sort_order ASC, p.name ASC
    ");
    $stmt->execute([$selected_category]);
}
$products = $stmt->fetchAll();

// Fetch all available categories for the filter buttons
$stmt_categories = $pdo->query("
    SELECT c.id, c.name, COUNT(p.id) as product_count
    FROM categories c
    INNER JOIN products p ON c.id = p.category_id
    WHERE c.status = 'active' AND p.status = 'active' AND p.stock_quantity > 0
    GROUP BY c.id, c.name
    ORDER BY c.sort_order ASC, c.name ASC
");
$categories = $stmt_categories->fetchAll();

// Get total product count for "All Products" button
$stmt_total = $pdo->query("SELECT COUNT(*) as total FROM products WHERE status = 'active' AND stock_quantity > 0");
$total_products = $stmt_total->fetch()['total'];

// Get customer name if logged in
$customer_username = '';
if (isset($_SESSION['customer_id'])) {
    $customer_id = $_SESSION['customer_id'];
    $stmt_customer = $pdo->prepare("SELECT first_name, last_name FROM customers WHERE id = ?");
    $stmt_customer->execute([$customer_id]);
    $customer_data = $stmt_customer->fetch();
    if ($customer_data) {
        $customer_username = htmlspecialchars($customer_data['first_name'] . ' ' . $customer_data['last_name']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Available Products - Starbucks</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            color: #fff;
            text-shadow: 1px 1px 4px #005f3d88;
            display: block;
        }

        .logo p {
            margin-top: 0.2rem;
            font-size: 1.1rem;
        }

        .customer-info {
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

        .view-dashboard-btn {
            background: #00704A;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .view-dashboard-btn:hover {
            background: #005f3d;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            background: #fff;
            padding: 2rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: #00704A;
        }

        /* Category Filter Styles */
        .category-filters {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .category-btn {
            background: #e9ecef;
            color: #495057;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 20px;
            transition: all 0.3s;
            font-size: 0.9rem;
            font-weight: 500;
            border: 2px solid transparent;
        }

        .category-btn:hover {
            background: #00704A;
            color: white;
            transform: translateY(-1px);
        }

        .category-btn.active {
            background: #00704A;
            color: white;
            border-color: #005f3d;
        }

        .category-count {
            background: rgba(255,255,255,0.2);
            color: inherit;
            padding: 0.1rem 0.4rem;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-left: 0.3rem;
        }

        .products {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            justify-content: center;
        }

        .product-card {
            background: #fafafa;
            border: 1px solid #eee;
            border-radius: 10px;
            width: 260px;
            padding: 18px 14px 16px 14px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 2px 8px #0001;
            transition: box-shadow 0.2s, transform 0.2s;
            position: relative;
        }

        .product-card:hover {
            box-shadow: 0 4px 16px #0002;
            transform: translateY(-2px) scale(1.02);
        }

        .product-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            background: #eaeaea;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #bbb;
        }

        .product-name {
            font-size: 1.15em;
            font-weight: bold;
            margin-bottom: 6px;
            text-align: center;
            color: #00704A;
        }

        .product-desc {
            font-size: 0.97em;
            color: #555;
            margin-bottom: 10px;
            text-align: center;
            min-height: 40px;
        }

        .product-price {
            font-size: 1.1em;
            color: #1a7f37;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .product-category {
            font-size: 0.85em;
            color: #007bff;
            background: #e7f3ff;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            margin-bottom: 8px;
            text-transform: capitalize;
        }

        .featured {
            color: #e6b800;
            font-size: 1.3em;
            margin-bottom: 4px;
            position: absolute;
            top: 10px;
            right: 16px;
        }

        .stock-info {
            font-size:0.95em;
            color:#888;
            margin-bottom: 6px;
        }

        /* Add a custom class for the Starbucks Products text */
        .starbucks-products-title {
            font-size: 2.2rem;
            font-weight: bold;
            color: #fff;
            text-shadow: 2px 2px 8px #005f3d88;
            letter-spacing: 1px;
            margin-bottom: 0.2rem;
            display: block;
            text-align: left;
        }

        .no-products {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 1rem 0;
        }

        @media (max-width: 900px) {
            .products { gap: 16px; }
            .product-card { width: 90vw; max-width: 340px; }
            .container { padding: 1rem 0.5rem; }
            .category-filters { gap: 0.3rem; }
            .category-btn { font-size: 0.8rem; padding: 0.4rem 0.8rem; }
        }

        @media (max-width: 600px) {
            .header-content { flex-direction: column; gap: 1rem; }
            .container { padding: 0.5rem 0.2rem; }
            .category-filters { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <span class="starbucks-products-title">Starbucks Products</span>
                <p>Discover our menu!</p>
            </div>
            <div class="customer-info">
                <?php if ($customer_username): ?>
                    <span>Welcome, <?= $customer_username; ?>!</span>
                    <a href="customer_dashboard.php" class="view-dashboard-btn">Dashboard</a>
                    <a href="logout.php" class="logout-btn">Logout</a>
                <?php else: ?>
                    <a href="customer_login.php" class="view-dashboard-btn">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <div class="container">
        <h1>Available Products</h1>
        
        <!-- Category Filter Buttons -->
        <?php if (!empty($categories)): ?>
            <div class="category-filters">
                <a href="?category=all" class="category-btn <?= $selected_category === 'all' ? 'active' : '' ?>">
                    All Products
                    <span class="category-count"><?= $total_products ?></span>
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="?category=<?= urlencode($cat['id']) ?>" 
                       class="category-btn <?= $selected_category == $cat['id'] ? 'active' : '' ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                        <span class="category-count"><?= $cat['product_count'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="products">
            <?php if (empty($products)): ?>
                <div class="no-products">
                    <?php if ($selected_category === 'all'): ?>
                        No products available at the moment.
                    <?php else: ?>
                        <?php
                        // Get category name for display
                        $category_name = 'this category';
                        foreach ($categories as $cat) {
                            if ($cat['id'] == $selected_category) {
                                $category_name = $cat['name'];
                                break;
                            }
                        }
                        ?>
                        No products found in "<?= htmlspecialchars($category_name) ?>" category.
                        <br><a href="?category=all" style="color: #00704A; text-decoration: none;">← View all products</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <?php if ($product['is_featured']): ?>
                            <div class="featured" title="Featured">&#9733;</div>
                        <?php endif; ?>
                        <?php
                        $img = '';
                        if (!empty($product['image_url'])) {
                            $img = htmlspecialchars($product['image_url']);
                        }
                        ?>
                        <?php if ($img): ?>
                            <img src="<?= $img ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img">
                        <?php else: ?>
                            <div class="product-img">&#9749;</div>
                        <?php endif; ?>
                        <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                        <?php if (!empty($product['category_name'])): ?>
                            <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
                        <?php endif; ?>
                        <div class="product-desc"><?= htmlspecialchars($product['description']) ?></div>
                        <div class="product-price">$<?= number_format($product['price'], 2) ?></div>
                        <div class="stock-info">In stock: <?= (int)$product['stock_quantity'] ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a href="customer_dashboard.php" class="view-dashboard-btn" style="margin-top: 2rem;">← Back to Dashboard</a>
    </div>
</body>
</html>