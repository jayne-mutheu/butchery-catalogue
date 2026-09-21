<?php
session_start();

// Protect page: restricted to admins
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

$message = '';
$error   = '';

// 1. Handle Add New Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $category_id  = (int)($_POST['category_id'] ?? 0);
    $name         = trim($_POST['name'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $price_per_kg = (float)($_POST['price_per_kg'] ?? 0);

    if ($category_id > 0 && !empty($name) && $price_per_kg > 0) {
        $stmt = $pdo->prepare('INSERT INTO products (category_id, name, description, price_per_kg) VALUES (:category_id, :name, :description, :price_per_kg)');
        $stmt->execute([
            'category_id'  => $category_id,
            'name'         => $name,
            'description'  => $description,
            'price_per_kg' => $price_per_kg
        ]);
        $message = "Product '{$name}' added successfully!";
    } else {
        $error = "Please select a valid category, product name, and price greater than 0.";
    }
}

// 2. Handle Delete Product
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $deleteStmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $deleteStmt->execute(['id' => $delete_id]);
    header('Location: admin_products.php?deleted=1');
    exit;
}

if (isset($_GET['deleted'])) {
    $message = "Product removed successfully.";
}

// 3. Fetch Categories (Beef, Chicken, Goat / Lamb)
$categories = $pdo->query('SELECT * FROM categories ORDER BY id ASC')->fetchAll();

// 4. Fetch Existing Products grouped by Category
$products = $pdo->query('
    SELECT p.*, c.name AS category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY c.id ASC, p.name ASC
')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Meat Products - Butchery Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f6f9; color: #333; }
        header { background: #8b0000; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { margin: 0; font-size: 1.5rem; }
        header nav a { color: #fff; text-decoration: none; margin-left: 15px; font-weight: bold; }
        
        .container { max-width: 1100px; margin: 25px auto; padding: 0 20px; display: grid; grid-template-columns: 350px 1fr; gap: 25px; }
        
        .card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .card h2 { margin-top: 0; color: #8b0000; font-size: 1.2rem; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 0.85rem; font-weight: bold; margin-bottom: 5px; color: #555; }
        input, select, textarea { width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 0.9rem; }
        
        .btn-submit { background: #8b0000; color: white; border: none; padding: 10px 15px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 0.95rem; }
        .btn-submit:hover { background: #660000; }
        
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        th { background: #343a40; color: #fff; font-size: 0.8rem; text-transform: uppercase; }
        
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; background: #e9ecef; color: #333; }
        .btn-delete { color: #d9534f; text-decoration: none; font-weight: bold; font-size: 0.85rem; }
        .btn-delete:hover { text-decoration: underline; }
        
        .alert { padding: 10px 15px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9rem; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<header>
    <h1>Butchery Admin Panel</h1>
    <nav>
        <a href="admin.php">Orders Dashboard</a>
        <a href="admin_products.php" style="text-decoration: underline;">Manage Products</a>
        <a href="shop.php" target="_blank">View Storefront</a>
        <a href="logout.php" style="background: #343a40; padding: 6px 12px; border-radius: 4px;">Logout</a>
    </nav>
</header>

<div class="container">

    <!-- Add Product Form -->
    <div class="card">
        <h2>Add New Meat Product</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="admin_products.php">
            <input type="hidden" name="add_product" value="1">

            <div class="form-group">
                <label for="category_id">Select Meat Category</label>
                <select name="category_id" id="category_id" required>
                    <option value="">-- Choose Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" placeholder="e.g. Minced Meat, T-Bone, Whole Chicken" required>
            </div>

            <div class="form-group">
                <label for="price_per_kg">Price per KG (KES)</label>
                <input type="number" id="price_per_kg" name="price_per_kg" step="0.01" min="1" placeholder="e.g. 750 or 850" required>
            </div>

            <div class="form-group">
                <label for="description">Short Description</label>
                <textarea id="description" name="description" rows="3" placeholder="e.g. Freshly ground lean beef mince"></textarea>
            </div>

            <button type="submit" class="btn-submit">+ Add Product to Store</button>
        </form>
    </div>

    <!-- Existing Products Table -->
    <div class="card">
        <h2>Current Product Catalog</h2>

        <?php if (empty($products)): ?>
            <p style="color: #777;">No products added yet. Use the form on the left to add items under Beef, Chicken, or Goat / Lamb.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Product Name</th>
                        <th>Description</th>
                        <th>Price / KG</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $item): ?>
                        <tr>
                            <td><span class="badge"><?= htmlspecialchars($item['category_name']) ?></span></td>
                            <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                            <td><small><?= htmlspecialchars($item['description']) ?></small></td>
                            <td><strong>KES <?= number_format($item['price_per_kg'], 2) ?></strong></td>
                            <td>
                                <a href="admin_products.php?delete_id=<?= $item['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

</body>
</html>