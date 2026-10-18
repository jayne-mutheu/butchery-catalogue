<?php
session_start();
require_once 'db.php';

// Handle Item Removal
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['cart_id'])) {
    $remove_id = $_GET['cart_id'];
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            if (isset($item['cart_id']) && $item['cart_id'] === $remove_id) {
                unset($_SESSION['cart'][$key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
                $_SESSION['cart_message'] = "Item removed from cart.";
                break;
            }
        }
    }
    header('Location: cart.php');
    exit;
}

// Handle Clear Cart
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    $_SESSION['cart'] = [];
    $_SESSION['cart_message'] = "Cart cleared.";
    header('Location: cart.php');
    exit;
}

$cart_items = $_SESSION['cart'] ?? [];
$grand_total = 0;
foreach ($cart_items as $item) {
    $grand_total += (float)($item['total_price'] ?? 0);
}

$cart_count = count($cart_items);
$is_admin = !empty($_SESSION['is_admin']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Prime Cuts Butchery</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #f4f6f9; color: #333; }

        .navbar { background-color: #8b0000; color: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .navbar h1 { margin: 0; font-size: 1.5rem; }
        .navbar-links { display: flex; align-items: center; gap: 15px; }
        .navbar-links a { color: #fff; text-decoration: none; font-weight: bold; font-size: 0.95rem; }

        .btn-admin-nav { 
            background-color: #17a2b8; 
            color: #fff !important; 
            padding: 8px 14px; 
            border-radius: 20px; 
            font-size: 0.85rem !important; 
            display: flex; 
            align-items: center; 
            gap: 6px; 
        }

        .cart-btn { background-color: #ffc107; color: #333 !important; padding: 8px 16px; border-radius: 20px; display: flex; align-items: center; gap: 8px; font-weight: bold; text-decoration: none; }
        .cart-badge { background-color: #8b0000; color: #fff; border-radius: 50%; padding: 2px 7px; font-size: 0.8rem; }

        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .page-header h2 { margin: 0; color: #8b0000; }

        .table-responsive { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow-x: auto; border: 1px solid #eee; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 14px 18px; border-bottom: 1px solid #eee; }
        th { background-color: #f8f9fa; color: #555; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        td { font-size: 0.95rem; }

        .item-title { font-weight: bold; color: #222; }
        .item-cut { font-size: 0.8rem; color: #28a745; font-weight: bold; display: inline-block; background: #e8f5e9; padding: 2px 8px; border-radius: 12px; margin-top: 4px; }
        
        .btn-remove { color: #dc3545; text-decoration: none; font-weight: bold; font-size: 0.85rem; }
        .btn-remove:hover { text-decoration: underline; }

        .cart-summary { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #eee; padding: 20px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .total-box { font-size: 1.25rem; font-weight: bold; color: #222; }
        .total-amount { color: #8b0000; font-size: 1.5rem; }

        .action-buttons { display: flex; gap: 12px; }
        .btn { padding: 10px 20px; border-radius: 6px; font-weight: bold; text-decoration: none; font-size: 0.95rem; display: inline-block; text-align: center; }
        .btn-secondary { background-color: #6c757d; color: #fff; }
        .btn-secondary:hover { background-color: #5a6268; }
        .btn-checkout { background-color: #28a745; color: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.15); }
        .btn-checkout:hover { background-color: #218838; }
        .btn-clear { background-color: #fff; color: #dc3545; border: 1px solid #dc3545; }
        .btn-clear:hover { background-color: #f8d7da; }

        .alert { padding: 12px 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        .empty-cart { text-align: center; padding: 50px 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #eee; }
        .empty-cart h3 { color: #666; margin-bottom: 10px; }
    </style>
</head>
<body>

<nav class="navbar">
    <h1>🥩 Prime Cuts Butchery</h1>
    <div class="navbar-links">
        <a href="shop.php">Storefront</a>

        <?php if ($is_admin): ?>
            <a href="admin.php" class="btn-admin-nav">⚙️ Admin Dashboard</a>
        <?php endif; ?>

        <a href="cart.php" class="cart-btn">
            🛒 Cart <span class="cart-badge"><?= $cart_count ?></span>
        </a>

        <?php if (isset($_SESSION['user_id']) || $is_admin): ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h2>Shopping Cart</h2>
        <?php if (!empty($cart_items)): ?>
            <a href="cart.php?action=clear" class="btn btn-clear" onclick="return confirm('Clear all items from your cart?');">Clear Cart</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['cart_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['cart_message']) ?>
            <?php unset($_SESSION['cart_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <h3>Your cart is currently empty.</h3>
            <p style="color:#888; margin-bottom:20px;">Browse our fresh meat selection to add items to your cart.</p>
            <a href="shop.php" class="btn btn-secondary">Go to Storefront</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Meat Item</th>
                        <th>Cut Style</th>
                        <th>Price / KG</th>
                        <th>Weight / Mode</th>
                        <th>Total (KES)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <div class="item-title"><?= htmlspecialchars($item['name']) ?></div>
                            </td>
                            <td>
                                <span class="item-cut"><?= htmlspecialchars($item['cut_preference'] ?? 'Standard Cut') ?></span>
                            </td>
                            <td>KES <?= number_format((float)($item['price_per_kg'] ?? 0), 2) ?></td>
                            <td>
                                <strong><?= number_format((float)($item['weight_kg'] ?? 0), 3) ?> KG</strong>
                                <small style="color:#777; display:block;">
                                    (<?= ($item['buy_mode'] ?? 'weight') === 'amount' ? 'By KES Amount' : 'By Weight' ?>)
                                </small>
                            </td>
                            <td><strong>KES <?= number_format((float)($item['total_price'] ?? 0), 2) ?></strong></td>
                            <td>
                                <a href="cart.php?action=remove&cart_id=<?= urlencode($item['cart_id']) ?>" class="btn-remove" onclick="return confirm('Remove this item?');">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="cart-summary">
            <div class="total-box">
                Grand Total: <span class="total-amount">KES <?= number_format($grand_total, 2) ?></span>
            </div>
            <div class="action-buttons">
                <a href="shop.php" class="btn btn-secondary">← Continue Shopping</a>
                <a href="checkout.php" class="btn btn-checkout">Proceed to Checkout →</a>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>