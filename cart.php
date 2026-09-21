<?php
session_start();

// Handle individual item removal
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['key'])) {
    $remove_key = $_GET['key'];
    if (isset($_SESSION['cart'][$remove_key])) {
        unset($_SESSION['cart'][$remove_key]);
        $_SESSION['cart_message'] = "Item removed from your cart.";
    }
    header('Location: cart.php');
    exit;
}

// Handle emptying the entire cart
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    unset($_SESSION['cart']);
    $_SESSION['cart_message'] = "Cart cleared.";
    header('Location: cart.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$grand_total = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prime Cuts Butchery - Shopping Cart</title>
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
        .navbar-links { display: flex; align-items: center; gap: 20px; }
        .navbar-links a { color: #fff; text-decoration: none; font-weight: bold; font-size: 0.95rem; }
        
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .page-title { color: #8b0000; margin-bottom: 20px; }
        
        .cart-table { 
            width: 100%; 
            border-collapse: collapse; 
            background: #fff; 
            border-radius: 8px; 
            overflow: hidden; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); 
            margin-bottom: 20px; 
        }
        .cart-table th, .cart-table td { padding: 14px 18px; text-align: left; border-bottom: 1px solid #eee; }
        .cart-table th { background-color: #8b0000; color: #fff; font-weight: 600; font-size: 0.9rem; }
        .cart-table tr:hover { background-color: #fcfcfc; }

        .btn-remove { color: #dc3545; text-decoration: none; font-weight: bold; font-size: 0.85rem; }
        .btn-remove:hover { text-decoration: underline; }

        .cart-summary { 
            background: #fff; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 15px; 
        }
        .grand-total { font-size: 1.3rem; font-weight: bold; color: #8b0000; }

        .action-btns { display: flex; gap: 10px; align-items: center; }
        .btn { text-decoration: none; padding: 10px 18px; border-radius: 5px; font-weight: bold; font-size: 0.95rem; display: inline-block; }
        .btn-primary { background-color: #28a745; color: #fff; }
        .btn-primary:hover { background-color: #218838; }
        .btn-secondary { background-color: #6c757d; color: #fff; }
        .btn-secondary:hover { background-color: #5a6268; }

        .alert { padding: 12px 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .badge-mode { background: #e9ecef; color: #495057; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: bold; margin-left: 6px; }
    </style>
</head>
<body>

<nav class="navbar">
    <h1>🥩 Prime Cuts Butchery</h1>
    <div class="navbar-links">
        <a href="shop.php">🏪 Continue Shopping</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="logout.php">🚪 Logout</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    <h2 class="page-title">🛒 Your Selected Meat Products</h2>

    <?php if (isset($_SESSION['cart_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['cart_message']) ?>
            <?php unset($_SESSION['cart_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cart)): ?>
        <div class="alert alert-info" style="text-align: center; padding: 40px 20px;">
            <h3>Your cart is currently empty.</h3>
            <p>Browse our storefront to choose meat cuts by weight or budget.</p>
            <a href="shop.php" class="btn btn-primary" style="margin-top: 10px;">Go to Storefront</a>
        </div>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Meat Item</th>
                    <th>Cut Preference</th>
                    <th>Price / KG</th>
                    <th>Calculated Weight</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart as $key => $item): ?>
                    <?php 
                        $name = $item['name'] ?? 'Meat Product';
                        $cut = $item['cut_preference'] ?? 'Standard Cut';
                        $price_per_kg = (float)($item['price_per_kg'] ?? $item['price'] ?? 0);
                        $weight_kg = (float)($item['weight_kg'] ?? $item['weight'] ?? 0);
                        $buy_mode = $item['buy_mode'] ?? 'weight';
                        $subtotal = (float)($item['total_price'] ?? ($weight_kg * $price_per_kg));
                        
                        $grand_total += $subtotal;
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($name) ?></strong>
                            <?php if ($buy_mode === 'amount'): ?>
                                <span class="badge-mode">By Budget</span>
                            <?php else: ?>
                                <span class="badge-mode">By Weight</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($cut) ?></td>
                        <td>KES <?= number_format($price_per_kg, 2) ?></td>
                        <td><?= number_format($weight_kg, 3) ?> kg</td>
                        <td><strong>KES <?= number_format($subtotal, 2) ?></strong></td>
                        <td>
                            <a href="cart.php?action=remove&key=<?= urlencode($key) ?>" class="btn-remove" onclick="return confirm('Remove this item from your cart?')">Remove</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-summary">
            <div>
                <a href="cart.php?action=clear" class="btn btn-secondary" onclick="return confirm('Clear all items from your cart?')">Clear Cart</a>
                <a href="shop.php" class="btn btn-secondary">Continue Shopping</a>
            </div>
            <div class="action-btns">
                <span class="grand-total">Grand Total: KES <?= number_format($grand_total, 2) ?></span>
                <a href="checkout.php" class="btn btn-primary">Proceed to Checkout 💳</a>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>