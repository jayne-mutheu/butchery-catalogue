<?php
session_start();
require_once 'db.php';

$order_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('
    SELECT o.*, oi.weight_kg, oi.cut_preference, oi.unit_price, oi.subtotal, p.name AS product_name 
    FROM orders o 
    JOIN order_items oi ON o.id = oi.order_id 
    JOIN products p ON oi.product_id = p.id 
    WHERE o.id = :id
');
$stmt->execute(['id' => $order_id]);
$order = $stmt->fetch();

if (!$order) {
    die('Order not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Receipt #<?= $order['id'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f9; padding: 20px; }
        .receipt { background: #fff; max-width: 500px; margin: 30px auto; padding: 25px; border-radius: 8px; border-top: 5px solid #8b0000; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .receipt h2 { margin-top: 0; color: #8b0000; }
        .line { border-bottom: 1px solid #eee; padding: 8px 0; display: flex; justify-content: space-between; }
        .total { font-weight: bold; font-size: 1.2rem; border-top: 2px solid #333; margin-top: 15px; padding-top: 10px; }
        a { display: block; text-align: center; margin-top: 20px; color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
<div class="receipt">
    <h2>Prime Cuts Butchery Receipt</h2>
    <p><strong>Order ID:</strong> #<?= $order['id'] ?></p>
    <p><strong>Date:</strong> <?= $order['created_at'] ?></p>
    <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?> (<?= htmlspecialchars($order['phone']) ?>)</p>
    <p><strong>Status:</strong> <span style="text-transform: uppercase; color: orange; font-weight: bold;"><?= $order['status'] ?></span></p>

    <hr>
    <h3>Items</h3>
    <div class="line">
        <span><?= htmlspecialchars($order['product_name']) ?> (<?= $order['weight_kg'] ?> kg @ $<?= number_format($order['unit_price'], 2) ?>)</span>
        <span>$<?= number_format($order['subtotal'], 2) ?></span>
    </div>
    <p><small><strong>Cut Preference:</strong> <?= htmlspecialchars($order['cut_preference']) ?></small></p>

    <div class="line total">
        <span>Total Paid:</span>
        <span>$<?= number_format($order['total_amount'], 2) ?></span>
    </div>

    <a href="index.php">&larr; Back to Shop</a>
</div>
</body>
</html>