<?php
session_start();

// Ensure user is logged in as an administrator
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

$message = '';
$error   = '';

// Handle Adding New Meat Item to Inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_meat'])) {
    $name        = trim($_POST['name'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $stock_kg    = (float)($_POST['stock_kg'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if (!empty($name) && !empty($category) && $price > 0) {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO products (name, category, price, stock_kg, description) 
                VALUES (:name, :category, :price, :stock_kg, :description)
            ');
            $stmt->execute([
                'name'        => $name,
                'category'    => $category,
                'price'       => $price,
                'stock_kg'    => $stock_kg,
                'description' => $description
            ]);
            $message = "Successfully added '{$name}' ({$category}) to inventory!";
        } catch (PDOException $e) {
            $error = "Error adding product: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields (Name, Category, and Price).";
    }
}

// Handle Inventory Delete Request
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    try {
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $delete_id]);
        $message = "Item #{$delete_id} removed from inventory.";
    } catch (PDOException $e) {
        $error = "Failed to delete item: " . $e->getMessage();
    }
}

// Fetch all meat products from database
try {
    $stmt = $pdo->query('SELECT * FROM products ORDER BY id DESC');
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $error = "Error loading inventory: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meat Inventory Management - Admin</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #f4f6f9; color: #333; display: flex; min-height: 100vh; }
        
        /* Left Sidebar Layout */
        .sidebar { width: 260px; background-color: #8b0000; color: #fff; padding: 20px 0; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-brand { padding: 0 20px 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-brand h2 { margin: 0; font-size: 1.3rem; color: #fff; }
        .sidebar-brand small { color: #f2a9a9; font-size: 0.8rem; }
        
        .sidebar-menu { list-style: none; padding: 0; margin: 20px 0 0 0; flex-grow: 1; }
        .sidebar-menu li { margin-bottom: 4px; }
        .sidebar-menu a { display: block; padding: 12px 20px; color: #f8f9fa; text-decoration: none; font-weight: 600; font-size: 0.95rem; border-left: 4px solid transparent; transition: all 0.2s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: #660000; border-left-color: #ffc107; color: #fff; }
        
        .sidebar-user { padding: 20px; border-top: 1px solid rgba(255,255,255,0.15); font-size: 0.85rem; color: #f2a9a9; }
        .sidebar-user strong { color: #fff; }

        /* Main Content Area */
        .main-content { flex-grow: 1; padding: 30px; overflow-y: auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-header h1 { margin: 0; font-size: 1.8rem; color: #8b0000; }

        /* Form Card */
        .card { background: #fff; border-radius: 8px; padding: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 30px; }
        .card h2 { margin-top: 0; color: #333; font-size: 1.2rem; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 20px; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { font-size: 0.85rem; font-weight: bold; margin-bottom: 6px; color: #555; }
        .form-group input, .form-group select, .form-group textarea { padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 0.9rem; }
        .form-group textarea { resize: vertical; height: 70px; }
        
        .btn-submit { background: #8b0000; color: #fff; border: none; padding: 12px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 0.95rem; margin-top: 10px; transition: background 0.2s; }
        .btn-submit:hover { background: #660000; }

        /* Inventory Table */
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 0.9rem; vertical-align: middle; }
        th { background: #343a40; color: #fff; font-size: 0.8rem; text-transform: uppercase; }
        
        .badge-category { background: #e9ecef; color: #495057; padding: 4px 8px; border-radius: 12px; font-weight: bold; font-size: 0.75rem; display: inline-block; }
        .badge-stock { background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 12px; font-weight: bold; font-size: 0.75rem; }
        .badge-low { background: #f8d7da; color: #721c24; }

        .btn-delete { color: #dc3545; text-decoration: none; font-weight: bold; font-size: 0.85rem; }
        .btn-delete:hover { text-decoration: underline; }

        .alert { padding: 12px 15px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9rem; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<!-- Left Sidebar Navigation -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <h2>Prime Cuts Butchery</h2>
        <small>Admin Control Panel</small>
    </div>
    
    <ul class="sidebar-menu">
        <li><a href="admin.php">📦 Customer Orders</a></li>
        <li><a href="admin_inventory.php" class="active">🥩 Meat Inventory</a></li>
        <li><a href="shop.php" target="_blank">🏪 View Storefront</a></li>
        <li><a href="logout.php">🚪 Logout</a></li>
    </ul>

    <div class="sidebar-user">
        Logged in as:<br>
        <strong><?= htmlspecialchars($_SESSION['username'] ?? 'jane') ?></strong>
    </div>
</aside>

<!-- Main Content Area -->
<main class="main-content">

    <div class="page-header">
        <h1>Meat Inventory Management</h1>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Form: Add New Meat Type -->
    <div class="card">
        <h2>➕ Add New Meat Type / Stock Item</h2>
        <form method="POST" action="admin_inventory.php">
            <input type="hidden" name="add_meat" value="1">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Meat Cut / Item Name *</label>
                    <input type="text" id="name" name="name" placeholder="e.g. T-Bone Steak, Ribeye, Minced Meat" required>
                </div>

                <div class="form-group">
                    <label for="category">Meat Category / Type *</label>
                    <select id="category" name="category" required>
                        <option value="">-- Select Category --</option>
                        <option value="Beef">Beef</option>
                        <option value="Goat / Mutton">Goat / Mutton</option>
                        <option value="Pork">Pork</option>
                        <option value="Chicken / Poultry">Chicken / Poultry</option>
                        <option value="Offal & Specialty">Offal & Specialty</option>
                        <option value="Custom / Other">Custom / Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="price">Price per KG (KES) *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" placeholder="e.g. 850.00" required>
                </div>

                <div class="form-group">
                    <label for="stock_kg">Available Stock (KG)</label>
                    <input type="number" id="stock_kg" name="stock_kg" step="0.1" min="0" placeholder="e.g. 50.0">
                </div>

                <div class="form-group full-width">
                    <label for="description">Description / Cut Details</label>
                    <textarea id="description" name="description" placeholder="Optional details (e.g. Freshly slaughtered, lean cut, bone-in)..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit">Add Meat to Inventory</button>
        </form>
    </div>

    <!-- Inventory Table -->
    <div class="card">
        <h2>📋 Current Meat Inventory</h2>
        <?php if (empty($products)): ?>
            <p style="text-align: center; color: #777;">No meat items in inventory yet. Use the form above to add some!</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Meat Name</th>
                        <th>Category</th>
                        <th>Price / KG</th>
                        <th>Stock (KG)</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <?php 
                            $price = $p['price'] ?? $p['price_per_kg'] ?? 0;
                            $stock = $p['stock_kg'] ?? $p['stock'] ?? 0;
                            $cat   = $p['category'] ?? 'General';
                        ?>
                        <tr>
                            <td><strong>#<?= $p['id'] ?></strong></td>
                            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                            <td><span class="badge-category"><?= htmlspecialchars($cat) ?></span></td>
                            <td><strong>KES <?= number_format((float)$price, 2) ?></strong></td>
                            <td>
                                <span class="badge-stock <?= ($stock < 5) ? 'badge-low' : '' ?>">
                                    <?= number_format((float)$stock, 1) ?> kg
                                </span>
                            </td>
                            <td><?= htmlspecialchars($p['description'] ?? 'N/A') ?></td>
                            <td>
                                <a href="admin_inventory.php?delete_id=<?= $p['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to remove this item?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</main>

</body>
</html>