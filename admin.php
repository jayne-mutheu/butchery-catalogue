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

// Handle Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id   = (int)($_POST['order_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');

    $valid_statuses = ['Pending', 'Processing', 'Ready for Delivery', 'Completed', 'Cancelled'];

    if ($order_id > 0 && in_array($new_status, $valid_statuses)) {
        try {
            $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
            $stmt->execute([
                'status' => $new_status,
                'id'     => $order_id
            ]);
            $message = "Order #{$order_id} status updated to '{$new_status}'.";
        } catch (PDOException $e) {
            $error = "Failed to update order status: " . $e->getMessage();
        }
    } else {
        $error = "Invalid status update selection.";
    }
}

// Fetch all orders with customer details
try {
    $stmt = $pdo->query('
        SELECT 
            o.*, 
            u.username AS customer_name,
            u.email AS customer_email
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.id DESC
    ');
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
    $error  = "Error loading orders: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Orders - Admin Dashboard</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            background-color: #f4f6f9; 
            color: #333; 
            display: flex; 
            min-height: 100vh; 
        }
        
        /* Left Sidebar Navigation */
        .sidebar { 
            width: 260px; 
            background-color: #8b0000; 
            color: #fff; 
            padding: 20px 0; 
            display: flex; 
            flex-direction: column; 
            flex-shrink: 0; 
        }
        .sidebar-brand { 
            padding: 0 20px 20px 20px; 
            border-bottom: 1px solid rgba(255,255,255,0.15); 
        }
        .sidebar-brand h2 { 
            margin: 0; 
            font-size: 1.3rem; 
            color: #fff; 
        }
        .sidebar-brand small { 
            color: #f2a9a9; 
            font-size: 0.8rem; 
        }
        
        .sidebar-menu { 
            list-style: none; 
            padding: 0; 
            margin: 20px 0 0 0; 
            flex-grow: 1; 
        }
        .sidebar-menu li { 
            margin-bottom: 4px; 
        }
        .sidebar-menu a { 
            display: block; 
            padding: 12px 20px; 
            color: #f8f9fa; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 0.95rem; 
            border-left: 4px solid transparent; 
            transition: all 0.2s; 
        }
        .sidebar-menu a:hover, .sidebar-menu a.active { 
            background-color: #660000; 
            border-left-color: #ffc107; 
            color: #fff; 
        }
        
        .sidebar-user { 
            padding: 20px; 
            border-top: 1px solid rgba(255,255,255,0.15); 
            font-size: 0.85rem; 
            color: #f2a9a9; 
        }
        .sidebar-user strong { 
            color: #fff; 
        }

        /* Main Content Area */
        .main-content { 
            flex-grow: 1; 
            padding: 30px; 
            overflow-y: auto; 
        }
        .page-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 25px; 
        }
        .page-header h1 { 
            margin: 0; 
            font-size: 1.8rem; 
            color: #8b0000; 
        }

        /* Card container */
        .card { 
            background: #fff; 
            border-radius: 8px; 
            padding: 25px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); 
        }
        .card-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 2px solid #f0f0f0; 
            padding-bottom: 12px; 
            margin-bottom: 20px; 
        }
        .card-header h2 { 
            margin: 0; 
            color: #333; 
            font-size: 1.2rem; 
        }

        /* Table */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            background: #fff; 
        }
        th, td { 
            padding: 12px 15px; 
            text-align: left; 
            border-bottom: 1px solid #eee; 
            font-size: 0.9rem; 
            vertical-align: middle; 
        }
        th { 
            background: #343a40; 
            color: #fff; 
            font-size: 0.8rem; 
            text-transform: uppercase; 
        }
        
        /* Status Badges */
        .status-badge { 
            display: inline-block; 
            padding: 4px 10px; 
            border-radius: 12px; 
            font-size: 0.75rem; 
            font-weight: bold; 
            text-transform: uppercase; 
        }
        .status-Pending { background: #fff3cd; color: #856404; }
        .status-Processing { background: #cce5ff; color: #004085; }
        .status-Ready { background: #d1ecf1; color: #0c5460; }
        .status-Completed { background: #d4edda; color: #155724; }
        .status-Cancelled { background: #f8d7da; color: #721c24; }

        .status-form { 
            display: flex; 
            gap: 6px; 
            align-items: center; 
        }
        .status-form select { 
            padding: 6px; 
            font-size: 0.85rem; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
        }
        .btn-update { 
            background: #343a40; 
            color: #fff; 
            border: none; 
            padding: 6px 12px; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 0.8rem; 
            font-weight: bold; 
            transition: background 0.2s;
        }
        .btn-update:hover { 
            background: #1d2124; 
        }

        .alert { 
            padding: 12px 15px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            font-size: 0.9rem; 
        }
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
        <li><a href="admin.php" class="active">📦 Customer Orders</a></li>
        <li><a href="admin_inventory.php">🥩 Meat Inventory</a></li>
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
        <h1>Customer Orders Dashboard</h1>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>All Placed Orders</h2>
            <span>Total Orders: <strong><?= count($orders) ?></strong></span>
        </div>

        <?php if (empty($orders)): ?>
            <p style="text-align: center; color: #777; padding: 20px;">No customer orders placed yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Item Details</th>
                        <th>Weight</th>
                        <th>Cut Preference</th>
                        <th>Total (KES)</th>
                        <th>Current Status</th>
                        <th>Date & Time</th>
                        <th>Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php 
                            $itemName = $order['product_name'] ?? $order['item_name'] ?? $order['product'] ?? 'Meat Order';
                            $weight   = $order['weight'] ?? $order['quantity'] ?? 0;
                            $cut      = $order['cut_preference'] ?? $order['cut'] ?? 'Standard';
                            $total    = $order['total_price'] ?? $order['total'] ?? $order['price'] ?? 0;
                            $date     = isset($order['created_at']) ? date('M d, Y h:i A', strtotime($order['created_at'])) : 'N/A';
                            $status   = $order['status'] ?? 'Pending';
                        ?>
                        <tr>
                            <td><strong>#<?= $order['id'] ?></strong></td>
                            <td>
                                <strong><?= htmlspecialchars($order['customer_name'] ?? 'Guest Customer') ?></strong><br>
                                <small style="color: #666;"><?= htmlspecialchars($order['customer_email'] ?? '') ?></small>
                            </td>
                            <td><?= htmlspecialchars($itemName) ?></td>
                            <td><?= number_format((float)$weight, 2) ?> kg</td>
                            <td><?= htmlspecialchars($cut) ?></td>
                            <td><strong>KES <?= number_format((float)$total, 2) ?></strong></td>
                            <td>
                                <span class="status-badge status-<?= str_replace(' ', '', $status) ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>
                            <td><small><?= $date ?></small></td>
                            <td>
                                <form method="POST" action="admin.php" class="status-form">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    
                                    <select name="status" required>
                                        <?php 
                                        $statuses = ['Pending', 'Processing', 'Ready for Delivery', 'Completed', 'Cancelled'];
                                        foreach ($statuses as $st): 
                                        ?>
                                            <option value="<?= $st ?>" <?= ($status === $st) ? 'selected' : '' ?>>
                                                <?= $st ?>
                                            </option>
                                        <?php endforeach; ?>
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

</main>

</body>
</html>