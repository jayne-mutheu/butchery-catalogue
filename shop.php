<?php
session_start();
require_once 'db.php';

try {
    $stmt =$pdo->query('SELECT * FROM products ORDER BY id DESC');
    $products =$stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];$error = "Error loading products: " . $e->getMessage();
}

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prime Cuts Butchery - Online Storefront</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #f4f6f9; color: #333; }

        .navbar { background-color: #8b0000; color: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .navbar h1 { margin: 0; font-size: 1.5rem; }
        .navbar-links { display: flex; align-items: center; gap: 20px; }
        .navbar-links a { color: #fff; text-decoration: none; font-weight: bold; font-size: 0.95rem; }
        
        .cart-btn { background-color: #ffc107; color: #333 !important; padding: 8px 16px; border-radius: 20px; display: flex; align-items: center; gap: 8px; font-weight: bold; text-decoration: none; }
        .cart-badge { background-color: #8b0000; color: #fff; border-radius: 50%; padding: 2px 7px; font-size: 0.8rem; }

        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-title h2 { margin: 0; color: #8b0000; }
        .page-title p { margin: 5px 0 0 0; color: #666; font-size: 0.9rem; }

        .btn-batch-add { background-color: #28a745; color: #fff; border: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 1rem; box-shadow: 0 2px 5px rgba(0,0,0,0.15); }
        .btn-batch-add:hover { background-color: #218838; }

        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
        .product-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 20px; display: flex; flex-direction: column; justify-content: space-between; border: 1px solid #eee; position: relative; }
        
        .select-checkbox-wrapper { position: absolute; top: 15px; right: 15px; display: flex; align-items: center; gap: 5px; background: #fff3cd; padding: 4px 8px; border-radius: 4px; border: 1px solid #ffeeba; }
        .select-checkbox-wrapper label { font-size: 0.75rem; font-weight: bold; cursor: pointer; color: #856404; }

        .product-title { font-size: 1.2rem; margin: 0 0 5px 0; color: #222; padding-right: 80px; }
        .product-desc { font-size: 0.85rem; color: #666; margin-bottom: 12px; }
        .product-price { font-size: 1.25rem; font-weight: bold; color: #8b0000; margin-bottom: 15px; }
        
        /* Form Controls */
        .purchase-options { border-top: 1px solid #eee; padding-top: 15px; display: flex; flex-direction: column; gap: 10px; }
        .mode-toggle { display: flex; gap: 10px; margin-bottom: 5px; }
        .mode-toggle label { font-size: 0.8rem; font-weight: bold; color: #555; cursor: pointer; display: flex; align-items: center; gap: 4px; }

        .form-row { display: flex; gap: 10px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
        .form-group label { font-size: 0.75rem; font-weight: bold; margin-bottom: 4px; color: #555; }
        .form-group input, .form-group select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.85rem; }

        .calc-preview { font-size: 0.8rem; color: #28a745; font-weight: bold; min-height: 18px; margin-top: 2px; }

        .alert { padding: 12px 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>

<nav class="navbar">
    <h1>🥩 Prime Cuts Butchery</h1>
    <div class="navbar-links">
        <a href="shop.php">Storefront</a>
        <a href="cart.php" class="cart-btn">
            🛒 Cart <span class="cart-badge"><?= $cart_count ?></span>
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    <?php if (isset($_SESSION['cart_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['cart_message']) ?>
            <?php unset($_SESSION['cart_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['cart_error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['cart_error']) ?>
            <?php unset($_SESSION['cart_error']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="add_to_cart.php">
        <input type="hidden" name="batch_add" value="1">

        <div class="page-header">
            <div class="page-title">
                <h2>Fresh Meat & Special Cuts</h2>
                <p>Order by <strong>Weight (KG)</strong> or <strong>Amount (KES)</strong>. Check multiple items to add them all at once and pay once!</p>
            </div>
            <?php if (!empty($products)): ?>
                <button type="submit" class="btn-batch-add">🛒 Add Selected Meat to Cart</button>
            <?php endif; ?>
        </div>

        <?php if (empty($products)): ?>
            <div style="text-align: center; color: #777; padding: 40px 0;">
                <h3>No meat items available right now.</h3>
                <p>Please check back later or add items from the Admin Panel.</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as$p): ?>
                    <?php $price = (float)($p['price'] ?? $p['price_per_kg'] ?? 0); ?>
                    <div class="product-card">
                        
                        <!-- Multi-select checkbox -->
                        <div class="select-checkbox-wrapper">
                            <input type="checkbox" id="select_<?= $p['id'] ?>" name="items[<?= $p['id'] ?>][selected]" value="1">
                            <label for="select_<?= $p['id'] ?>">Select</label>
                        </div>

                        <div>
                            <h3 class="product-title"><?= htmlspecialchars($p['name']) ?></h3>
                            <p class="product-desc"><?= htmlspecialchars($p['description'] ?? 'Fresh butchery cut.') ?></p>
                            <div class="product-price">
                                KES <?= number_format($price, 2) ?> <small style="font-size:0.8rem; color:#666;">/ kg</small>
                            </div>
                        </div>

                        <!-- Purchase Options -->
                        <div class="purchase-options">
                            <!-- Toggle Mode -->
                            <div class="mode-toggle">
                                <label>
                                    <input type="radio" name="items[<?= $p['id'] ?>][buy_mode]" value="weight" checked onchange="toggleBuyMode(<?= $p['id'] ?>, 'weight', <?=$price ?>)">
                                    By Weight (KG)
                                </label>
                                <label>
                                    <input type="radio" name="items[<?= $p['id'] ?>][buy_mode]" value="amount" onchange="toggleBuyMode(<?= $p['id'] ?>, 'amount', <?=$price ?>)">
                                    By Amount (KES)
                                </label>
                            </div>

                            <div class="form-row">
                                <!-- Input Box (Swaps between KG and KES via JS) -->
                                <div class="form-group" id="input_container_<?= $p['id'] ?>">
                                    <label id="input_label_<?= $p['id'] ?>">Weight (KG):</label>
                                    <input type="number" id="input_field_<?= $p['id'] ?>" name="items[<?= $p['id'] ?>][weight_kg]" step="0.1" value="1.0" min="0.1" oninput="calculatePreview(<?= $p['id'] ?>, <?=$price ?>)">
                                </div>

                                <div class="form-group">
                                    <label>Cut Style:</label>
                                    <select name="items[<?= $p['id'] ?>][cut_preference]">
                                        <option value="Standard Cut">Standard Cut</option>
                                        <option value="Steak Cut">Steak Cut</option>
                                        <option value="Minced / Ground">Minced / Ground</option>
                                        <option value="Boneless">Boneless</option>
                                        <option value="Curry Cut">Curry Cut</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Realtime Estimate Preview -->
                            <div class="calc-preview" id="preview_<?= $p['id'] ?>">
                                Total: KES <?= number_format($price, 2) ?>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
function toggleBuyMode(id, mode, pricePerKg) {
    const container = document.getElementById('input_container_' + id);
    const label = document.getElementById('input_label_' + id);
    
    // Auto check the selection checkbox when user edits inputs
    document.getElementById('select_' + id).checked = true;

    if (mode === 'amount') {
        label.innerText = 'Amount (KES):';
        container.innerHTML = `
            <label>Amount (KES):</label>
            <input type="number" id="input_field_${id}" name="items[${id}][amount_kes]" step="50" value="500" min="50" oninput="calculatePreview(${id}, ${pricePerKg})">
        `;
    } else {
        label.innerText = 'Weight (KG):';
        container.innerHTML = `
            <label>Weight (KG):</label>
            <input type="number" id="input_field_${id}" name="items[${id}][weight_kg]" step="0.1" value="1.0" min="0.1" oninput="calculatePreview(${id}, ${pricePerKg})">
        `;
    }
    calculatePreview(id, pricePerKg);
}

function calculatePreview(id, pricePerKg) {
    // Auto check the selection checkbox when user changes inputs
    document.getElementById('select_' + id).checked = true;

    const inputField = document.getElementById('input_field_' + id);
    const preview = document.getElementById('preview_' + id);
    const mode = document.querySelector(`input[name="items[${id}][buy_mode]"]:checked`).value;
    const val = parseFloat(inputField.value) || 0;

    if (mode === 'amount') {
        const approxKg = (val / pricePerKg).toFixed(3);
        preview.innerText = `Approx Weight: ${approxKg} KG`;
    } else {
        const totalKes = (val * pricePerKg).toFixed(2);
        preview.innerText = `Total: KES ${Number(totalKes).toLocaleString()}`;
    }
}
</script>

</body>
</html>