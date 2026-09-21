<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        // Retrieve admin account
        $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = :username AND is_admin = 1');
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['is_admin'] = true;

            header('Location: admin.php');
            exit;
        } else {
            $error = 'Invalid admin credentials or unauthorized access.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Prime Cuts</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #212529; margin: 0; color: #fff; }
        .login-box { background: #343a40; padding: 2.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); width: 320px; }
        h2 { margin-top: 0; color: #f8f9fa; text-align: center; }
        .form-group { margin-bottom: 1.2rem; }
        label { display: block; margin-bottom: .4rem; font-size: 0.9rem; }
        input { width: 100%; padding: .6rem; border: 1px solid #495057; background: #212529; color: #fff; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: .7rem; background: #8b0000; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; font-weight: bold; margin-top: 0.5rem; }
        button:hover { background: #a00000; }
        .error { color: #ff6b6b; font-size: 0.88rem; margin-bottom: 1rem; text-align: center; }
        .back-link { text-align: center; margin-top: 1.2rem; font-size: 0.85rem; }
        .back-link a { color: #adb5bd; text-decoration: none; }
        .back-link a:hover { color: #fff; }
    </style>
</head>
<body>
<div class="login-box">
    <h2>Admin Sign In</h2>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form action="admin-login.php" method="POST">
        <div class="form-group">
            <label for="username">Admin Username</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Access Dashboard</button>
    </form>
    <div class="back-link">
        <a href="index.php">&larr; Back to Portal Selection</a>
    </div>
</div>
</body>
</html>