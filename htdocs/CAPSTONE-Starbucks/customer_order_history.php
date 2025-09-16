<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: customer_login.php');
    exit();
}
$customer_id = intval($_SESSION['customer_id']);

// Reorder handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reorder') {
    $order_id = intval($_POST['order_id'] ?? 0);
    if ($order_id > 0) {
        // Load items and add to cart
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $stmt = $connection->prepare('SELECT product_id, product_name, quantity, unit_price, selected_size FROM order_items WHERE order_id = ?');
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $pid = intval($row['product_id']);
            $size = $row['selected_size'] ?: 'Grande';
            $key = $pid.'_'.$size;
            if (!isset($_SESSION['cart'][$key])) {
                $_SESSION['cart'][$key] = [
                    'product_id' => $pid,
                    'name' => $row['product_name'],
                    'price' => floatval($row['unit_price']),
                    'quantity' => intval($row['quantity']),
                    'size' => $size
                ];
            } else {
                $_SESSION['cart'][$key]['quantity'] += intval($row['quantity']);
            }
        }
        $stmt->close();
        header('Location: customer_place_order.php');
        exit();
    }
}

// Load orders
$orders = [];
$stmtO = $connection->prepare('SELECT id, order_number, final_amount, status, created_at FROM orders WHERE customer_id = ? ORDER BY created_at DESC');
$stmtO->bind_param('i', $customer_id);
$stmtO->execute();
$orders = $stmtO->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
$stmtO->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Orders</title>
  <style>
    body{font-family:Segoe UI,Tahoma,Geneva,Verdana,sans-serif;background:#f6f7fb;margin:0}
    .container{max-width:1000px;margin:0 auto;padding:24px}
    .card{background:#fff;border-radius:16px;box-shadow:0 8px 25px rgba(0,0,0,.08);padding:20px;margin-bottom:20px}
    .row{display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr;gap:10px}
    .btn{background:linear-gradient(135deg,#00704a,#28a745);color:#fff;border:none;padding:8px 12px;border-radius:8px;cursor:pointer}
    .tag{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px}
    .status-pending{background:#fff3cd;color:#856404}
    .status-preparing{background:#ffe0b2;color:#8d4b12}
    .status-ready{background:#e0f7fa;color:#006064}
    .status-completed{background:#e8f5e9;color:#1b5e20}
    .status-cancelled{background:#fdecea;color:#b71c1c}
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h2 style="margin-top:0;color:#00704a">Order History</h2>
      <div style="overflow:auto">
        <table style="width:100%;border-collapse:collapse">
          <thead>
            <tr style="background:#f8f9fa">
              <th style="text-align:left;padding:10px;border-bottom:1px solid #eee">Order #</th>
              <th style="text-align:left;padding:10px;border-bottom:1px solid #eee">Date</th>
              <th style="text-align:left;padding:10px;border-bottom:1px solid #eee">Amount</th>
              <th style="text-align:left;padding:10px;border-bottom:1px solid #eee">Status</th>
              <th style="text-align:left;padding:10px;border-bottom:1px solid #eee">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($orders)): ?>
              <tr><td colspan="5" style="text-align:center;color:#666;padding:16px">No orders yet.</td></tr>
            <?php else: foreach ($orders as $o): ?>
              <tr>
                <td style="padding:10px;border-bottom:1px solid #eee">#<?php echo htmlspecialchars($o['order_number']); ?></td>
                <td style="padding:10px;border-bottom:1px solid #eee"><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($o['created_at']))); ?></td>
                <td style="padding:10px;border-bottom:1px solid #eee">₱<?php echo number_format((float)$o['final_amount'],2); ?></td>
                <td style="padding:10px;border-bottom:1px solid #eee">
                  <?php $s = $o['status']; $cls = 'status-'.$s; ?>
                  <span class="tag <?php echo $cls; ?>"><?php echo htmlspecialchars($s); ?></span>
                </td>
                <td style="padding:10px;border-bottom:1px solid #eee;display:flex;gap:8px;align-items:center">
                  <a class="btn" href="order_receipt.php?order_id=<?php echo intval($o['id']); ?>" target="_blank">Receipt</a>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="reorder">
                    <input type="hidden" name="order_id" value="<?php echo intval($o['id']); ?>">
                    <button class="btn" type="submit">Reorder</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>


