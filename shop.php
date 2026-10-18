<?php
session_start();
require_once 'db.php';

// Redirect user to the login page if they are not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch all available products from the database
try {
    $stmt = $pdo->query('SELECT * FROM products ORDER BY id DESC');
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $error = "Failed to load products: " . $e->getMessage();
}

$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$is_admin = !empty($_SESSION['is_admin']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storefront - Prime Cuts Butchery</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-red: #e63946;
            --secondary-red: #d90429;
            --text-dark: #1a1a1a;
            --text-muted: #777;
            --bg-page: #fcfcfc;
            --white: #ffffff;
            --border-color: #eaeaea;
            --success: #28a745;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: var(--bg-page); color: var(--text-dark); line-height: 1.5; }

        /* Navigation - Flat with Border */
        .navbar { background-color: #000; color: var(--white); padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid #333; }
        .navbar h1 { font-size: 1.3rem; font-weight: 900; letter-spacing: 0.5px; display: flex; align-items: center; gap: 10px; }
        .navbar-links { display: flex; align-items: center; gap: 20px; }
        .navbar-links a { color: var(--white); text-decoration: none; font-weight: 600; font-size: 0.95rem; }
        
        .cart-btn { background-color: var(--white); color: #000 !important; padding: 8px 18px; border-radius: 25px; display: flex; align-items: center; gap: 8px; font-weight: bold; border: 1px solid var(--border-color); }
        .cart-badge { background-color: var(--primary-red); color: var(--white); border-radius: 50%; padding: 2px 8px; font-size: 0.8rem; }

        /* Internal Store Banner - Flat */
        .store-banner { background: var(--white); border-bottom: 1px solid var(--border-color); padding: 30px 5%; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .store-banner h2 { color: var(--primary-red); font-size: 1.8rem; font-weight: 900; }
        .store-banner p { color: var(--text-muted); }

        .container { max-width: 1300px; margin: 0 auto 40px; padding: 0 5%; }
        
        .alert { padding: 15px 20px; border-radius: 6px; margin-bottom: 30px; font-weight: 600; border: 1px solid transparent; }
        .alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }

        /* Product Grid */
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        
        /* Flat Product Cards */
        .product-card { background: var(--white); border-radius: 16px; overflow: hidden; border: 1px solid var(--border-color); display: flex; flex-direction: column; transition: border-color 0.2s ease; }
        .product-card:hover { border-color: #ccc; }
        
        .product-img-placeholder { height: 180px; background: #e2e2e2; display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 3rem; border-bottom: 1px solid var(--border-color); }
        
        .product-details { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
        .product-category { font-size: 0.75rem; text-transform: uppercase; color: var(--primary-red); font-weight: 800; margin-bottom: 5px; letter-spacing: 1px; }
        .product-name { font-size: 1.1rem; font-weight: 900; margin-bottom: 10px; color: var(--text-dark); text-transform: uppercase; }
        .product-desc { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 15px; flex-grow: 1; }
        .product-price { font-size: 1.3rem; font-weight: 900; color: var(--text-dark); margin-bottom: 20px; border-top: 1px solid var(--border-color); padding-top: 15px; }

        /* Direct Add to Cart Form - Flat */
        .direct-cart-form { background: #fafafa; padding: 15px; border-radius: 8px; border: 1px solid var(--border-color); margin-top: auto; }
        .form-row { display: flex; gap: 10px; margin-bottom: 12px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
        .form-group label { font-size: 0.75rem; font-weight: 700; margin-bottom: 4px; color: var(--text-muted); text-transform: uppercase; }
        .form-control { padding: 8px 10px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.9rem; outline: none; background: #fff; }
        .form-control:focus { border-color: var(--primary-red); }
        
        .btn-add-cart { width: 100%; background: #000; color: var(--white); border: none; padding: 12px; font-size: 0.9rem; font-weight: 800; border-radius: 25px; cursor: pointer; transition: background 0.2s; display: flex; justify-content: center; align-items: center; gap: 8px; text-transform: uppercase; }
        .btn-add-cart:hover { background: #333; }
    </style>
</head>
<body>

<nav class="navbar">
    <h1><i class="fas fa-drumstick-bite"></i> PRIME CUTS</h1>
    <div class="navbar-links">
        <a href="index.php">Home</a>
        <?php if ($is_admin): ?>
            <a href="admin.php">Admin</a>
        <?php endif; ?>
        
        <a href="cart.php" class="cart-btn">
            <i class="fas fa-shopping-bag"></i> 
            <span class="cart-badge"><?= $cart_count ?></span>
        </a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="store-banner">
    <div>
        <h2>AVAILABLE CUTS</h2>
        <p>Browse our fresh cuts and select exactly how you want them prepared.</p>
    </div>
    <div style="font-size: 0.85rem; color: #777; font-weight: 600;">
        ACCOUNT: <span style="color:#000; text-transform:uppercase;"><?= htmlspecialchars($_SESSION['username'] ?? 'Customer') ?></span>
    </div>
</div>

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

    <?php if (empty($products)): ?>
        <div style="text-align: center; padding: 50px; color: #777; border: 1px solid var(--border-color); border-radius: 16px; background: #fff;">
            <h3>No products available at the moment.</h3>
            <p>Our butchers are currently updating the stock.</p>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $p): 
                $price = (float)($p['price'] ?? $p['price_per_kg'] ?? 0);
                $category = $p['category'] ?? $p['category_name'] ?? 'Premium Cut';
            ?>
                <div class="product-card">
                    <div class="product-img-placeholder">
                        Image Lost
                    </div>
                    
                    <div class="product-details">
                        <span class="product-category"><?= htmlspecialchars($category) ?></span>
                        <h4 class="product-name"><?= htmlspecialchars($p['name']) ?></h4>
                        <p class="product-desc"><?= htmlspecialchars($p['description'] ?? 'Fresh cut prepared to order.') ?></p>
                        <div class="product-price">KES <?= number_format($price, 2) ?> <small style="font-size: 0.75rem; color: #777; text-transform:uppercase;">/ KG</small></div>
                        
                        <form method="POST" action="cart_process.php" class="direct-cart-form">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Buy By</label>
                                    <select name="buy_mode" class="form-control" onchange="toggleInput(this, <?= $p['id'] ?>)">
                                        <option value="weight">Weight (KG)</option>
                                        <option value="amount">Amount (KES)</option>
                                    </select>
                                </div>
                                <div class="form-group" id="input-container-<?= $p['id'] ?>">
                                    <label id="label-<?= $p['id'] ?>">Weight (KG)</label>
                                    <input type="number" name="weight_kg" class="form-control" step="0.1" min="0.1" value="1.0" id="input-val-<?= $p['id'] ?>">
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label>Preparation</label>
                                <select name="cut_preference" class="form-control">
                                    <option value="Standard Cut">Standard Cut</option>
                                    <option value="Cubed / Diced">Cubed / Diced</option>
                                    <option value="Minced">Minced</option>
                                    <option value="Whole / Intact">Whole / Intact</option>
                                </select>
                            </div>

                            <button type="submit" class="btn-add-cart">
                                Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
    function toggleInput(selectElement, productId) {
        const mode = selectElement.value;
        const inputField = document.getElementById('input-val-' + productId);
        const label = document.getElementById('label-' + productId);

        if (mode === 'amount') {
            inputField.name = 'amount_kes';
            inputField.step = '10';
            inputField.min = '50';
            inputField.value = '500'; 
            label.innerText = 'Amount (KES)';
        } else {
            inputField.name = 'weight_kg';
            inputField.step = '0.1';
            inputField.min = '0.1';
            inputField.value = '1.0'; 
            label.innerText = 'Weight (KG)';
        }
    }
</script>

</body>
</html>