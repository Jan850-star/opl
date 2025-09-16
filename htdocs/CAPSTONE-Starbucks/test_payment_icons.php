<?php
// Test file to debug payment method icons
session_start();
require_once 'db_connect.php';

// Fetch payment methods
$payment_methods_query = "SELECT method_code, method_name, icon FROM payment_methods WHERE is_active = TRUE ORDER BY method_name ASC";
$payment_methods_result = mysqli_query($connection, $payment_methods_query);

if (!$payment_methods_result) {
    die("Database query failed: " . mysqli_error($connection));
}

$payment_methods = mysqli_fetch_all($payment_methods_result, MYSQLI_ASSOC);

// Custom icons mapping
$custom_icons = [
    'cash' => '/CAPSTONE/cash.png',
    'card' => '/CAPSTONE/card.png',
    'digital_wallet' => '/CAPSTONE/digital_wallet.png',
    'gcash' => '/CAPSTONE/digital_wallet.png'
];

// Add custom icons to payment methods
foreach ($payment_methods as &$method) {
    $method['custom_icon'] = isset($custom_icons[$method['method_code']]) ? $custom_icons[$method['method_code']] : $method['icon'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Icons Test</title>
    <style>
        .payment-test {
            display: flex;
            gap: 20px;
            margin: 20px;
        }
        .payment-option {
            border: 2px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .payment-icon {
            width: 48px;
            height: 48px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
        }
        .debug-info {
            background: #f0f0f0;
            padding: 10px;
            margin: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>Payment Icons Test</h1>
    
    <div class="debug-info">
        <h3>Debug Information:</h3>
        <pre><?php print_r($payment_methods); ?></pre>
    </div>
    
    <div class="payment-test">
        <?php foreach ($payment_methods as $method): ?>
            <div class="payment-option">
                <h4><?php echo htmlspecialchars($method['method_name']); ?></h4>
                <img src="<?php echo htmlspecialchars($method['custom_icon']); ?>" 
                     alt="<?php echo htmlspecialchars($method['method_name']); ?>" 
                     class="payment-icon"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div style="display:none; color:red;">Image not found: <?php echo htmlspecialchars($method['custom_icon']); ?></div>
                <p>Code: <?php echo htmlspecialchars($method['method_code']); ?></p>
                <p>Icon Path: <?php echo htmlspecialchars($method['custom_icon']); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="debug-info">
        <h3>File Check:</h3>
        <p>Check if these files exist in your htdocs/CAPSTONE/ directory:</p>
        <ul>
            <li>cash.png</li>
            <li>card.png</li>
            <li>digital_wallet.png</li>
        </ul>
    </div>
</body>
</html>
