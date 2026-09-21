<?php
session_start();
require_once 'db.php';

$cart = $_SESSION['cart'] ?? [];

// Redirect to cart if empty
if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

// Calculate total cart price safely
$grand_total = 0;
foreach ($cart as $item) {
    $weight_kg = (float)($item['weight_kg'] ?? $item['weight'] ?? 0);
    $price_per_kg = (float)($item['price_per_kg'] ?? $item['price'] ?? 0);
    $subtotal = (float)($item['total_price'] ?? ($weight_kg * $price_per_kg));
    $grand_total += $subtotal;
}

$error = '';

// Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $user_id       = $_SESSION['user_id'] ?? NULL;

    if (empty($customer_name) || empty($phone)) {
        $error = "Please provide your full name and phone number.";
    } else {
        try {
            $pdo->beginTransaction();

            // Insert each cart item into the orders table
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, product_name, weight, cut_preference, total_price, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'Pending', NOW())
            ");

            foreach ($cart as $item) {
                $p_name  = $item['name'] ?? 'Meat Product';
                $p_cut   = $item['cut_preference'] ?? 'Standard Cut';
                $p_weight= (float)($item['weight_kg'] ?? $item['weight'] ?? 0);
                $p_pk    = (float)($item['price_per_kg'] ?? $item['price'] ?? 0);
                $p_total = (float)($item['total_price'] ?? ($p_weight * $p_pk));

                $stmt->execute([
                    $user_id,
                    $p_name,
                    $p_weight,
                    $p_cut,
                    $p_total
                ]);
            }

            $pdo->commit();

            // Clear the cart and set confirmation message
            unset($_SESSION['cart']);
            $_SESSION['cart_message'] = "🎉 Order placed successfully! We are preparing your fresh cuts.";
            header('Location: shop.php');
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error saving your order: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prime Cuts Butchery - Order Checkout</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            background-color: #f4f6f9; 
            color: #333; 
        }

        .navbar { 
            background-color: #8b0000; 
            color: #fff; 
            padding: 15px 40px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
        }
        .navbar h1 { margin: 0; font-size: 1.5rem; }
        .navbar-links a { color: #fff; text-decoration: none; font-weight: bold; }

        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .page-title { color: #8b0000; margin-bottom: 20px; }

        .checkout-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        @media (max-width: 768px) { .checkout-grid { grid-template-columns: 1fr; } }

        .card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #eee; }
        .card h3 { margin-top: 0; color: #8b0000; border-bottom: 2px solid #8b0000; padding-bottom: 8px; }

        .summary-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px dashed #eee; }
        .summary-item:last-child { border-bottom: none; }
        .item-details small { color: #666; display: block; }

        .total-row { display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold; margin-top: 15px; padding-top: 15px; border-top: 2px solid #333; color: #8b0000; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 0.85rem; color: #444; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9rem; }

        .btn-submit { background-color: #28a745; color: #fff; border: none; padding: 12px; width: 100%; border-radius: 5px; font-weight: bold; font-size: 1rem; cursor: pointer; transition: background 0.2s; }
        .btn-submit:hover { background-color: #218838; }

        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>

<nav class="navbar">
    <h1>🥩 Prime Cuts Butchery</h1>
    <div class="navbar-links">
        <a href="cart.php">← Back to Cart</a>
    </div>
</nav>

<div class="container">
    <h2 class="page-title">💳 Order Checkout</h2>

    <?php if (!empty($error)): ?>
        <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="checkout-grid">
        <!-- Order Items Summary -->
        <div class="card">
            <h3>Order Summary</h3>
            <?php foreach ($cart as $item): ?>
                <?php 
                    $name = $item['name'] ?? 'Meat Product';
                    $cut = $item['cut_preference'] ?? 'Standard Cut';
                    $weight = (float)($item['weight_kg'] ?? $item['weight'] ?? 0);
                    $price_pk = (float)($item['price_per_kg'] ?? $item['price'] ?? 0);
                    $subtotal = (float)($item['total_price'] ?? ($weight * $price_pk));
                ?>
                <div class="summary-item">
                    <div class="item-details">
                        <strong><?= htmlspecialchars($name) ?></strong>
                        <small><?= htmlspecialchars($cut) ?> (<?= number_format($weight, 3) ?> kg)</small>
                    </div>
                    <div>
                        <strong>KES <?= number_format($subtotal, 2) ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="total-row">
                <span>Total Amount Payable:</span>
                <span>KES <?= number_format($grand_total, 2) ?></span>
            </div>
        </div>

        <!-- Customer & Delivery Form -->
        <div class="card">
            <h3>Customer & Delivery Details</h3>
            <form method="POST" action="checkout.php">
                <div class="form-group">
                    <label for="customer_name">Full Name *</label>
                    <input type="text" id="customer_name" name="customer_name" required placeholder="e.g. John Doe">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number (M-Pesa) *</label>
                    <input type="text" id="phone" name="phone" required placeholder="e.g. 0712345678">
                </div>

                <div class="form-group">
                    <label for="address">Delivery Location / Address</label>
                    <textarea id="address" name="address" rows="3" placeholder="e.g. Estate, House No, Street name"></textarea>
                </div>

                <button type="submit" class="btn-submit">Confirm & Place Order</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>