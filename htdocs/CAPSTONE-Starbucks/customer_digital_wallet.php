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

// Handle cash-in request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cash_in') {
    $amount = floatval($_POST['amount']);
    
    if ($amount > 0 && $amount <= 50000) { // Maximum cash-in limit of 50,000
        try {
            $connection->begin_transaction();
            
            // Get or create wallet
            $stmt_wallet = $connection->prepare("SELECT id, balance FROM digital_wallet WHERE customer_id = ?");
            $stmt_wallet->bind_param("i", $customer_id);
            $stmt_wallet->execute();
            $wallet_result = $stmt_wallet->get_result();
            
            if ($wallet_result->num_rows > 0) {
                $wallet = $wallet_result->fetch_assoc();
                $wallet_id = $wallet['id'];
                $current_balance = $wallet['balance'];
            } else {
                // Create new wallet
                $stmt_create = $connection->prepare("INSERT INTO digital_wallet (customer_id, balance) VALUES (?, 0.00)");
                $stmt_create->bind_param("i", $customer_id);
                $stmt_create->execute();
                $wallet_id = $connection->insert_id;
                $current_balance = 0.00;
            }
            
            $new_balance = $current_balance + $amount;
            
            // Update wallet balance
            $stmt_update = $connection->prepare("UPDATE digital_wallet SET balance = ? WHERE id = ?");
            $stmt_update->bind_param("di", $new_balance, $wallet_id);
            $stmt_update->execute();
            
            // Record transaction (if table exists)
            try {
                $stmt_transaction = $connection->prepare("INSERT INTO wallet_transactions (customer_id, wallet_id, transaction_type, amount, balance_before, balance_after, description) VALUES (?, ?, 'cash_in', ?, ?, ?, ?)");
                $description = "Cash-in: ₱" . number_format($amount, 2);
                $stmt_transaction->bind_param("iiddds", $customer_id, $wallet_id, $amount, $current_balance, $new_balance, $description);
                $stmt_transaction->execute();
                $stmt_transaction->close();
            } catch (Exception $e) {
                // Table might not exist yet, continue without recording transaction
                error_log("Wallet transactions table not found: " . $e->getMessage());
            }
            
            $connection->commit();
            $success_message = "Successfully cashed in ₱" . number_format($amount, 2) . " to your digital wallet!";
            
        } catch (Exception $e) {
            $connection->rollback();
            $error_message = "Error processing cash-in. Please try again.";
        }
    } else {
        $error_message = "Invalid amount. Please enter an amount between ₱1 and ₱50,000.";
    }
}

// Get wallet balance
$stmt_wallet = $connection->prepare("SELECT balance FROM digital_wallet WHERE customer_id = ?");
$stmt_wallet->bind_param("i", $customer_id);
$stmt_wallet->execute();
$wallet_result = $stmt_wallet->get_result();
$wallet_balance = 0.00;
if ($wallet_result->num_rows > 0) {
    $wallet_data = $wallet_result->fetch_assoc();
    $wallet_balance = $wallet_data['balance'];
}
$stmt_wallet->close();

// Get recent wallet transactions (if table exists)
$transactions = [];
try {
    $stmt_transactions = $connection->prepare("
        SELECT transaction_type, amount, balance_after, description, created_at 
        FROM wallet_transactions 
        WHERE customer_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt_transactions->bind_param("i", $customer_id);
    $stmt_transactions->execute();
    $transactions_result = $stmt_transactions->get_result();
    $transactions = mysqli_fetch_all($transactions_result, MYSQLI_ASSOC);
    $stmt_transactions->close();
} catch (Exception $e) {
    // Table might not exist yet, continue with empty transactions
    error_log("Wallet transactions table not found: " . $e->getMessage());
    $transactions = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Wallet - Starbucks</title>
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

        .customer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .customer-info span {
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

        .wallet-balance {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, #00704a, #28a745);
            color: white;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 112, 74, 0.3);
        }

        .wallet-balance h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }

        .wallet-balance .amount {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .wallet-balance .currency {
            font-size: 1.5rem;
            opacity: 0.8;
        }

        .cash-in-form {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #00704a;
            box-shadow: 0 0 0 3px rgba(0, 112, 74, 0.1);
        }

        .quick-amounts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .quick-amount-btn {
            background: white;
            border: 2px solid #ddd;
            padding: 0.75rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .quick-amount-btn:hover {
            border-color: #00704a;
            background: #f0fff5;
        }

        .quick-amount-btn.active {
            border-color: #00704a;
            background: #00704a;
            color: white;
        }

        .cash-in-btn {
            width: 100%;
            background: linear-gradient(135deg, #00704a, #28a745);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 112, 74, 0.3);
        }

        .cash-in-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #005a3c, #1e7e34);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 112, 74, 0.4);
        }

        .cash-in-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .transactions-table th,
        .transactions-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .transactions-table th {
            background: #f8f9fa;
            font-weight: bold;
        }

        .transaction-type {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .transaction-cash_in {
            background: #d4edda;
            color: #155724;
        }

        .transaction-payment {
            background: #f8d7da;
            color: #721c24;
        }

        .transaction-refund {
            background: #fff3cd;
            color: #856404;
        }

        .amount-positive {
            color: #28a745;
            font-weight: bold;
        }

        .amount-negative {
            color: #dc3545;
            font-weight: bold;
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
                padding: 1.5rem;
            }

            .wallet-balance .amount {
                font-size: 2rem;
            }

            .quick-amounts {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>💳 Digital Wallet</h1>
                <p>Starbucks Customer Portal</p>
            </div>
            <div class="customer-info">
                <span>Welcome, <?php echo $customer_username; ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <!-- Navigation Menu -->
    <nav class="nav-menu">
        <div class="nav-content">
            <a href="customer_dashboard.php" class="nav-item">
                🏠 Dashboard
            </a>
            <a href="customer_place_order.php" class="nav-item">
                🛒 Place Order
            </a>
            <a href="customer_digital_wallet.php" class="nav-item active">
                💳 Digital Wallet
            </a>
            <a href="customer_product.php" class="nav-item">
                📋 Browse Products
            </a>
            <a href="customer_profile.php" class="nav-item">
                👤 Profile
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

        <!-- Wallet Balance -->
        <div class="wallet-balance">
            <h3>Your Digital Wallet Balance</h3>
            <div class="amount">₱<?php echo number_format($wallet_balance, 2); ?></div>
            <div class="currency">Available for purchases</div>
        </div>

        <!-- Cash In Section -->
        <div class="section">
            <h2>💰 Cash In Money</h2>
            <div class="cash-in-form">
                <form method="POST" id="cash-in-form">
                    <input type="hidden" name="action" value="cash_in">
                    
                    <div class="form-group">
                        <label for="amount">Amount to Cash In</label>
                        <div class="quick-amounts">
                            <button type="button" class="quick-amount-btn" data-amount="100">₱100</button>
                            <button type="button" class="quick-amount-btn" data-amount="500">₱500</button>
                            <button type="button" class="quick-amount-btn" data-amount="1000">₱1,000</button>
                            <button type="button" class="quick-amount-btn" data-amount="2000">₱2,000</button>
                            <button type="button" class="quick-amount-btn" data-amount="5000">₱5,000</button>
                            <button type="button" class="quick-amount-btn" data-amount="10000">₱10,000</button>
                        </div>
                        <input type="number" id="amount" name="amount" min="1" max="50000" step="0.01" placeholder="Enter amount" required>
                        <small style="color: #666; font-size: 0.9rem;">Minimum: ₱1 | Maximum: ₱50,000</small>
                    </div>
                    
                    <button type="submit" class="cash-in-btn">Cash In Money</button>
                </form>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="section">
            <h2>📊 Transaction History</h2>
            <div style="overflow-x: auto;">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Balance After</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <span class="transaction-type transaction-<?php echo $transaction['transaction_type']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $transaction['transaction_type'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?php echo $transaction['transaction_type'] === 'cash_in' || $transaction['transaction_type'] === 'refund' ? 'amount-positive' : 'amount-negative'; ?>">
                                        <?php echo $transaction['transaction_type'] === 'cash_in' || $transaction['transaction_type'] === 'refund' ? '+' : '-'; ?>₱<?php echo number_format($transaction['amount'], 2); ?>
                                    </span>
                                </td>
                                <td>₱<?php echo number_format($transaction['balance_after'], 2); ?></td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($transaction['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No transactions found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Quick amount selection
        document.querySelectorAll('.quick-amount-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.quick-amount-btn').forEach(b => b.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Set the amount input value
                document.getElementById('amount').value = this.dataset.amount;
            });
        });

        // Form validation
        document.getElementById('cash-in-form').addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('amount').value);
            
            if (amount < 1) {
                e.preventDefault();
                alert('Minimum cash-in amount is ₱1.00');
                return false;
            }
            
            if (amount > 50000) {
                e.preventDefault();
                alert('Maximum cash-in amount is ₱50,000.00');
                return false;
            }
            
            if (!confirm(`Confirm cash-in of ₱${amount.toFixed(2)} to your digital wallet?`)) {
                e.preventDefault();
                return false;
            }
        });

        // Auto-hide messages after 5 seconds
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

        // Add smooth animations
        document.querySelectorAll('.quick-amount-btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 12px rgba(0, 112, 74, 0.2)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
    </script>
</body>
</html>
