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

// Fetch active products with stock > 0
$stmt = $pdo->query("SELECT * FROM products WHERE status = 'active' AND stock_quantity > 0 ORDER BY is_featured DESC, sort_order ASC, name ASC");
$products = $stmt->fetchAll();

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

        @media (max-width: 900px) {
            .products { gap: 16px; }
            .product-card { width: 90vw; max-width: 340px; }
            .container { padding: 1rem 0.5rem; }
        }

        @media (max-width: 600px) {
            .header-content { flex-direction: column; gap: 1rem; }
            .container { padding: 0.5rem 0.2rem; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <span class="starbucks-products-title">Starbucks Products</span>
                <p>Discover our menu!</p>
                <!-- Optionally, you can remove the old h1 below if you want only one title -->
                <!-- <h1>Starbucks Products</h1> -->
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
        <div class="products">
            <?php if (empty($products)): ?>
                <p>No products available.</p>
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