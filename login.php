<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        // Query using distinct placeholders to avoid SQLSTATE[HY093] parameter count mismatch
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1');
        $stmt->execute([
            'username' => $username,
            'email'    => $username
        ]);
        $user = $stmt->fetch();

        // Verify password hash
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = (bool)($user['is_admin'] ?? false);

            // Redirect based on user role
            if ($_SESSION['is_admin']) {
                header('Location: admin.php');
            } else {
                header('Location: shop.php');
            }
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Prime Cuts Butchery</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f8f9fa; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        .login-card { 
            background: #ffffff; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); 
            width: 100%; 
            max-width: 360px; 
        }
        .login-card h2 { 
            color: #8b0000; 
            margin-top: 0; 
            text-align: center; 
        }
        .form-group { 
            margin-bottom: 15px; 
        }
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
            font-size: 0.9rem; 
            color: #333333; 
        }
        input { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #cccccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
            font-size: 0.95rem; 
        }
        .btn-submit { 
            width: 100%; 
            background: #8b0000; 
            color: #ffffff; 
            border: none; 
            padding: 12px; 
            border-radius: 4px; 
            font-weight: bold; 
            font-size: 1rem; 
            cursor: pointer; 
        }
        .btn-submit:hover { 
            background: #660000; 
        }
        .alert { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 15px; 
            font-size: 0.88rem; 
            text-align: center; 
        }
        .footer-link { 
            text-align: center; 
            margin-top: 15px; 
            font-size: 0.85rem; 
        }
        .footer-link a { 
            color: #8b0000; 
            text-decoration: none; 
            font-weight: bold; 
        }
    </style>
</head>
<body>

<div class="login-card">
    <h2>Login to Prime Cuts</h2>

    <?php if (!empty($error)): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="username">Username or Email</label>
            <input type="text" id="username" name="username" required placeholder="Enter username or email" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Enter password">
        </div>

        <button type="submit" class="btn-submit">Login</button>
    </form>

    <div class="footer-link">
        Don't have an account? <a href="register.php">Register here</a>
    </div>
</div>

</body>
</html>