<?php
session_start();
require_once 'db.php';

$cart_items = $_SESSION['cart'] ?? [];

// Redirect to shop if cart is empty
if (empty($cart_items)) {
    $_SESSION['cart_error'] = "Your cart is empty. Please add items before checking out.";
    header('Location: shop.php');
    exit;
}

// Calculate grand total
$grand_total = 0;
foreach ($cart_items as $item) {
    $grand_total += (float)($item['total_price'] ?? 0);
}

$cart_count = count($cart_items);
$is_admin = !empty($_SESSION['is_admin']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

$errors = [];
$success_order_id = null;

// Process Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $notes         = trim($_POST['notes'] ?? '');

    if (empty($customer_name)) {
        $errors[] = "Customer name is required.";
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert into orders table
            $stmt = $pdo->prepare("
                INSERT INTO orders (customer_name, phone, address, notes, total_amount, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'Pending', NOW())
            ");
            $stmt->execute([$customer_name, $phone, $address, $notes, $grand_total]);
            $order_id = $pdo->lastInsertId();

            // Insert each item into order_items table
            $stmt_item = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, item_name, weight_kg, price_per_kg, cut_preference, total_price) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($cart_items as $item) {
                $stmt_item->execute([
                    $order_id,
                    (int)($item['product_id'] ?? 0),
                    $item['name'] ?? 'Unknown Item',
                    (float)($item['weight_kg'] ?? 0),
                    (float)($item['price_per_kg'] ?? 0),
                    $item['cut_preference'] ?? 'Standard Cut',
                    (float)($item['total_price'] ?? 0)
                ]);
            }

            $pdo->commit();

            // Clear session cart and store success flag
            $_SESSION['cart'] = [];
            $success_order_id = $order_id;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Failed to place order: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Prime Cuts Butchery</title>
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
        .page-header { margin-bottom: 25px; }
        .page-header h2 { margin: 0; color: #8b0000; }

        .checkout-grid { display: grid; grid-template-columns: 1fr 380px; gap: 25px; }
        @media (max-width: 768px) { .checkout-grid { grid-template-columns: 1fr; } }

        .card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 25px; border: 1px solid #eee; }
        .card h3 { margin-top: 0; color: #222; border-bottom: 2px solid #8b0000; padding-bottom: 8px; font-size: 1.1rem; }

        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; }
        .form-group label { font-size: 0.85rem; font-weight: bold; margin-bottom: 5px; color: #555; }
        .form-group input, .form-group textarea { padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.95rem; }
        .form-group textarea { resize: vertical; min-height: 70px; }

        .order-item-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px dashed #eee; font-size: 0.9rem; }
        .order-item-row:last-child { border-bottom: none; }
        
        .total-row { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 2px solid #8b0000; font-weight: bold; font-size: 1.15rem; margin-top: 10px; }

        .btn-submit { background-color: #28a745; color: #fff; border: none; padding: 14px; border-radius: 6px; font-weight: bold; font-size: 1.05rem; cursor: pointer; width: 100%; margin-top: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.15); }
        .btn-submit:hover { background-color: #218838; }

        .alert { padding: 12px 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .success-box { text-align: center; padding: 40px 20px; }
        .success-box h2 { color: #28a745; margin-bottom: 10px; }
        .btn-home { background-color: #8b0000; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; margin-top: 20px; }
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
    <?php if ($success_order_id): ?>
        <div class="card success-box">
            <h2>🎉 Order Placed Successfully!</h2>
            <p>Thank you for shopping with Prime Cuts Butchery.</p>
            <p>Your Order ID is <strong>#<?= htmlspecialchars($success_order_id) ?></strong>.</p>
            <p style="color:#666; font-size:0.9rem;">We have received your request and will prepare your cuts shortly.</p>
            <a href="shop.php" class="btn-home">Back to Storefront</a>
        </div>
    <?php else: ?>

        <div class="page-header">
            <h2>Complete Your Order</h2>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $err): ?>
                    <div>• <?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="checkout.php">
            <div class="checkout-grid">
                
                <!-- Customer Details Form -->
                <div class="card">
                    <h3>Customer & Delivery Details</h3>
                    
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="customer_name" required value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>" placeholder="e.g. John Doe">
                    </div>

                    <div class="form-group">
                        <label>Phone Number (M-Pesa / Call) *</label>
                        <input type="text" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="e.g. 0712345678">
                    </div>

                    <div class="form-group">
                        <label>Delivery Address / Location</label>
                        <input type="text" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" placeholder="e.g. Estate, Building, or Street">
                    </div>

                    <div class="form-group">
                        <label>Special Instructions / Notes</label>
                        <textarea name="notes" placeholder="e.g. Separate packaging, specific delivery time..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="card">
                    <h3>Order Summary</h3>

                    <?php foreach ($cart_items as $item): ?>
                        <div class="order-item-row">
                            <div>
                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                <br>
                                <small style="color:#666;">
                                    <?= number_format((float)($item['weight_kg'] ?? 0), 3) ?> KG (<?= htmlspecialchars($item['cut_preference'] ?? 'Standard Cut') ?>)
                                </small>
                            </div>
                            <div>
                                KES <?= number_format((float)($item['total_price'] ?? 0), 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="total-row">
                        <span>Total:</span>
                        <span style="color:#8b0000;">KES <?= number_format($grand_total, 2) ?></span>
                    </div>

                    <button type="submit" class="btn-submit">Confirm & Place Order</button>
                </div>

            </div>
        </form>

    <?php endif; ?>
</div>

</body>
</html>