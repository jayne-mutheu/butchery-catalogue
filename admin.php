<?php
session_start();
require_once 'db.php';

// Check if user is logged in as admin
$is_admin = !empty($_SESSION['is_admin']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if (!$is_admin) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');

    $allowed_statuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];
    if ($order_id > 0 && in_array($new_status, $allowed_statuses)) {
        try {
            $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $stmt->execute([$new_status, $order_id]);
            $message = "Order #$order_id status updated to '$new_status'.";
        } catch (PDOException $e) {
            $error = "Failed to update order status: " . $e->getMessage();
        }
    }
}

// Handle Order Filter
$status_filter = $_GET['status'] ?? 'All';
$where_clause = '';
$params = [];

if (in_array($status_filter, ['Pending', 'Processing', 'Completed', 'Cancelled'])) {
    $where_clause = 'WHERE status = ?';
    $params[] = $status_filter;
}

// Fetch Orders
try {
    $stmt = $pdo->prepare("SELECT * FROM orders $where_clause ORDER BY id DESC");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
    $error = "Error fetching orders: " . $e->getMessage();
}

// Fetch Order Items grouped by order_id
$order_items_map = [];
if (!empty($orders)) {
    $order_ids = array_column($orders, 'id');
    $in_clause = implode(',', array_fill(0, count($order_ids), '?'));
    try {
        $stmt_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($in_clause)");
        $stmt_items->execute($order_ids);
        $all_items = $stmt_items->fetchAll();
        foreach ($all_items as $item) {
            $order_items_map[$item['order_id']][] = $item;
        }
    } catch (PDOException $e) {
        // Soft fail on items fetch
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Prime Cuts Butchery</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-red: #e92543;
            --text-dark: #111111;
            --text-muted: #888888;
            --bg-page: #fcfcfc;
            --border-color: #ebebeb;
            --table-header: #f9f9f9;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: var(--bg-page); color: var(--text-dark); line-height: 1.5; }

        /* Top Admin Bar */
        .top-bar { background-color: #050505; color: #ffffff; padding: 8px 5%; font-size: 0.75rem; display: flex; justify-content: space-between; align-items: center; font-weight: 600; text-transform: uppercase; }
        .top-bar .highlight { color: var(--primary-red); }

        /* Main Header */
        .header-main { background-color: #ffffff; padding: 15px 5%; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); gap: 20px; }
        
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-dark); }
        .brand-icon { width: 45px; height: 45px; border: 1px solid var(--border-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: var(--text-dark); }
        .brand-text h1 { font-size: 1.1rem; font-weight: 900; letter-spacing: 0.5px; margin: 0; text-transform: uppercase; }
        .brand-text span { font-size: 0.65rem; color: var(--primary-red); font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; }

        .header-actions { display: flex; align-items: center; gap: 15px; }
        
        .btn-store { background-color: #ffffff; color: var(--text-dark); padding: 10px 20px; border: 1px solid var(--border-color); border-radius: 25px; text-decoration: none; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; transition: border-color 0.2s; }
        .btn-store:hover { border-color: var(--primary-red); color: var(--primary-red); }
        
        .btn-logout { background-color: #000000; color: #ffffff; padding: 10px 24px; border-radius: 25px; text-decoration: none; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; }

        /* Sub Navigation */
        .sub-nav { background: #ffffff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; }
        .sub-nav-links { display: flex; gap: 30px; }
        .sub-nav-links a { text-decoration: none; color: var(--text-muted); font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .sub-nav-links a.active { color: var(--text-dark); border-bottom: 2px solid var(--primary-red); padding-bottom: 13px; margin-bottom: -15px; }

        /* Main Content */
        .container { max-width: 1400px; margin: 40px auto; padding: 0 5%; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-header h2 { font-size: 1.6rem; font-weight: 900; text-transform: uppercase; }

        /* Filter Bar */
        .filter-bar { display: flex; gap: 10px; align-items: center; }
        .filter-btn { padding: 8px 16px; border-radius: 25px; text-decoration: none; font-size: 0.8rem; font-weight: 700; background: #ffffff; color: var(--text-muted); border: 1px solid var(--border-color); text-transform: uppercase; }
        .filter-btn.active { background: #000000; color: #ffffff; border-color: #000000; }

        /* Flat Card & Table */
        .card-flat { background: #ffffff; border-radius: 16px; border: 1px solid var(--border-color); overflow: hidden; margin-bottom: 25px; }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 18px 20px; border-bottom: 1px solid var(--border-color); vertical-align: top; }
        th { background-color: var(--table-header); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; }
        td { font-size: 0.9rem; font-weight: 500; }

        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; display: inline-block; text-transform: uppercase; border: 1px solid transparent; }
        .badge-pending { background: #fff8e1; color: #f57f17; border-color: #ffecb3; }
        .badge-processing { background: #e3f2fd; color: #1565c0; border-color: #bbdefb; }
        .badge-completed { background: #e8f5e9; color: #2e7d32; border-color: #c8e6c9; }
        .badge-cancelled { background: #ffebee; color: #c62828; border-color: #ffcdd2; }

        .item-list { list-style: none; padding: 0; margin: 0; }
        .item-list li { font-size: 0.85rem; border-bottom: 1px dashed var(--border-color); padding-bottom: 8px; margin-bottom: 8px; }
        .item-list li:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }

        /* Action Form */
        .status-form { display: flex; gap: 8px; align-items: center; }
        .status-select { padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); font-size: 0.8rem; font-weight: 600; outline: none; background: #fff; }
        .btn-update { padding: 8px 16px; background: #000; color: #fff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.8rem; text-transform: uppercase; }
        .btn-update:hover { background: #333; }

        /* Alerts */
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 0.9rem; border: 1px solid transparent; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border-color: #c8e6c9; }
        .alert-danger { background: #ffebee; color: #c62828; border-color: #ffcdd2; }

        .empty-state { padding: 60px 20px; text-align: center; color: var(--text-muted); }
        .empty-state h3 { color: var(--text-dark); margin-bottom: 10px; font-weight: 900; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="top-bar">
    <span>SYSTEM: <span class="highlight">SECURE ADMIN PORTAL</span></span>
    <span>Logged in as: <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
</div>

<header class="header-main">
    <a href="admin.php" class="brand">
        <div class="brand-icon"><i class="fas fa-shield-alt"></i></div>
        <div class="brand-text">
            <h1>CONTROL PANEL</h1>
            <span>PRIME CUTS MANAGEMENT</span>
        </div>
    </a>

    <div class="header-actions">
        <!-- Direct link to the public store for the admin to view -->
        <a href="shop.php" class="btn-store" target="_blank">
            <i class="fas fa-store"></i> View Public Store
        </a>
        <a href="admin_products.php" class="btn-store">
            <i class="fas fa-box"></i> Products
        </a>
        <a href="logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>

<nav class="sub-nav">
    <div class="sub-nav-links">
        <a href="admin.php" class="active"><i class="fas fa-clipboard-list"></i> Customer Orders</a>
        <a href="admin_inventory.php"><i class="fas fa-warehouse"></i> Inventory Stock</a>
    </div>
</nav>

<div class="container">

    <?php if ($message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <h2>Order Management</h2>

        <div class="filter-bar">
            <?php foreach (['All', 'Pending', 'Processing', 'Completed', 'Cancelled'] as $st): ?>
                <a href="admin.php?status=<?= $st ?>" class="filter-btn <?= $status_filter === $st ? 'active' : '' ?>">
                    <?= $st ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card-flat">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; color: #ddd;"></i>
                <h3>No orders found</h3>
                <p>No customer orders match the selected filter status.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Details</th>
                        <th>Requested Cuts</th>
                        <th>Total (KES)</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <?php 
                            $badge_class = 'badge-pending';
                            if ($o['status'] === 'Processing') $badge_class = 'badge-processing';
                            if ($o['status'] === 'Completed') $badge_class = 'badge-completed';
                            if ($o['status'] === 'Cancelled') $badge_class = 'badge-cancelled';
                        ?>
                        <tr>
                            <td>
                                <strong>#<?= $o['id'] ?></strong>
                                <br>
                                <span style="color:var(--text-muted); font-size: 0.75rem; font-weight: 700;">
                                    <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?>
                                </span>
                            </td>

                            <td>
                                <strong><?= htmlspecialchars($o['customer_name']) ?></strong><br>
                                <span style="font-size: 0.8rem; color: #555;"><i class="fas fa-phone-alt" style="font-size:0.7rem; color:var(--text-muted);"></i> <?= htmlspecialchars($o['phone']) ?></span><br>
                                <span style="font-size: 0.8rem; color: #555;"><i class="fas fa-map-marker-alt" style="font-size:0.7rem; color:var(--text-muted);"></i> <?= htmlspecialchars($o['address'] ?: 'N/A') ?></span>
                                <?php if (!empty($o['notes'])): ?>
                                    <div style="margin-top:6px; padding:6px; background:#fff9e6; border:1px solid #ffecb3; border-radius:4px; font-size:0.75rem; color:#856404;">
                                        <strong>Note:</strong> <?= htmlspecialchars($o['notes']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (!empty($order_items_map[$o['id']])): ?>
                                    <ul class="item-list">
                                        <?php foreach ($order_items_map[$o['id']] as $item): ?>
                                            <li>
                                                <strong><?= htmlspecialchars($item['item_name']) ?></strong> - 
                                                <?= number_format((float)$item['weight_kg'], 3) ?> KG
                                                <span style="color:var(--text-muted); font-size:0.75rem;">(<?= htmlspecialchars($item['cut_preference']) ?>)</span>
                                                <br>
                                                <span style="color:var(--primary-red); font-weight:800; font-size:0.8rem;">KES <?= number_format((float)$item['total_price'], 2) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <em style="color:var(--text-muted);">No items detailed</em>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong style="font-size: 1.1rem; color:var(--text-dark);">KES <?= number_format((float)$o['total_amount'], 2) ?></strong>
                            </td>

                            <td>
                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($o['status']) ?></span>
                            </td>

                            <td>
                                <form method="POST" action="admin.php?status=<?= urlencode($status_filter) ?>" class="status-form">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="status" class="status-select">
                                        <option value="Pending" <?= $o['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="Processing" <?= $o['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="Completed" <?= $o['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="Cancelled" <?= $o['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" class="btn-update">Save</button>
                                </form>
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