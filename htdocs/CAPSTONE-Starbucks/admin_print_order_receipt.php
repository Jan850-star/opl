<?php
require_once 'db_connect.php';
session_start();

$order_id = intval($_GET['order_id'] ?? 0);
if ($order_id <= 0) { die('Invalid order'); }

// Load order
$stmt = $connection->prepare("SELECT o.*, c.first_name, c.last_name, c.email FROM orders o JOIN customers c ON c.id = o.customer_id WHERE o.id = ? LIMIT 1");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$order) { die('Order not found'); }

// Load items
$items = [];
$stmtI = $connection->prepare("SELECT product_name, quantity, unit_price, total_price, selected_size FROM order_items WHERE order_id = ? ORDER BY id ASC");
$stmtI->bind_param('i', $order_id);
$stmtI->execute();
$items = $stmtI->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
$stmtI->close();

// Store info
$store = [
  'name' => 'Store',
  'address' => '',
  'phone' => ''
];
$res = $connection->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('store_name','store_address','store_phone')");
while ($row = $res->fetch_assoc()) {
  if ($row['setting_key'] === 'store_name') $store['name'] = $row['setting_value'];
  if ($row['setting_key'] === 'store_address') $store['address'] = $row['setting_value'];
  if ($row['setting_key'] === 'store_phone') $store['phone'] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Receipt #<?php echo htmlspecialchars($order['order_number']); ?></title>
  <style>
    body{font-family:Segoe UI,Tahoma,Geneva,Verdana,sans-serif;background:#f6f7fb;margin:0;padding:20px}
    .paper{max-width:800px;margin:0 auto;background:#fff;border-radius:12px;box-shadow:0 8px 25px rgba(0,0,0,.08);padding:24px}
    .header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #00704a;padding-bottom:12px;margin-bottom:16px}
    .title{color:#00704a;margin:0}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #eee;text-align:left}
    th{background:#f8f9fa}
    .summary{margin-top:12px}
    .row{display:flex;justify-content:space-between;margin:6px 0}
    .total{font-weight:700;color:#00704a;font-size:18px}
    .print{margin-top:16px}
    .btn{background:#00704a;color:#fff;border:none;padding:10px 16px;border-radius:8px;cursor:pointer}
  </style>
  <script>function printPage(){window.print()}</script>
</head>
<body>
  <div class="paper">
    <div class="header">
      <div>
        <h2 class="title"><?php echo htmlspecialchars($store['name']); ?></h2>
        <div><?php echo htmlspecialchars($store['address']); ?></div>
        <div><?php echo htmlspecialchars($store['phone']); ?></div>
      </div>
      <div>
        <div><strong>Order:</strong> #<?php echo htmlspecialchars($order['order_number']); ?></div>
        <div><strong>Date:</strong> <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($order['created_at']))); ?></div>
        <div><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></div>
        <div><strong>Payment:</strong> <?php echo htmlspecialchars($order['payment_method'] ?? ''); ?></div>
      </div>
    </div>
    <div style="margin-bottom:12px;">
      <div><strong>Customer:</strong> <?php echo htmlspecialchars($order['first_name'].' '.$order['last_name']); ?> (<?php echo htmlspecialchars($order['email']); ?>)</div>
    </div>
    <table>
      <thead>
        <tr>
          <th>Item</th>
          <th>Size</th>
          <th>Qty</th>
          <th>Unit</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><?php echo htmlspecialchars($it['product_name']); ?></td>
            <td><?php echo htmlspecialchars($it['selected_size'] ?? ''); ?></td>
            <td><?php echo (int)$it['quantity']; ?></td>
            <td>₱<?php echo number_format((float)$it['unit_price'],2); ?></td>
            <td>₱<?php echo number_format((float)$it['total_price'],2); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="summary">
      <div class="row"><span>Subtotal</span><span>₱<?php echo number_format((float)$order['total_amount'],2); ?></span></div>
      <div class="row"><span>Discount</span><span>-₱<?php echo number_format((float)$order['discount_amount'],2); ?></span></div>
      <div class="row"><span>Tax</span><span>₱<?php echo number_format((float)$order['tax_amount'],2); ?></span></div>
      <div class="row total"><span>Total</span><span>₱<?php echo number_format((float)$order['final_amount'],2); ?></span></div>
    </div>
    <div class="print"><button class="btn" onclick="printPage()">Print</button></div>
  </div>
</body>
</html>


