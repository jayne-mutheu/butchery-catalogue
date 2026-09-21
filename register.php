<?php
session_start();
require_once 'db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic Validations
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = 'All fields are required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    // Process Registration if no validation errors exist
    if (empty($errors)) {
        // Check if username or email already exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email');
        $stmt->execute(['username' => $username, 'email' => $email]);

        if ($stmt->fetch()) {
            $errors[] = 'Username or email address is already taken.';
        } else {
            // Secure Password Hashing
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insertStmt = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (:username, :email, :password)');
            
            if ($insertStmt->execute(['username' => $username, 'email' => $email, 'password' => $hashedPassword])) {
                $_SESSION['flash_success'] = 'Registration successful! You can now log in.';
                header('Location: login.php');
                exit;
            } else {
                $errors[] = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f4f4f9; margin: 0; }
        .register-box { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 340px; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: .4rem; font-weight: bold; }
        input { width: 100%; padding: .5rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: .7rem; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: .5rem; }
        button:hover { background: #218838; }
        .error-list { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: .75rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.88rem; }
        .error-list ul { margin: 0; padding-left: 1.2rem; }
        .login-link { margin-top: 1rem; text-align: center; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="register-box">
    <h2>Create Account</h2>

    <?php if (!empty($errors)): ?>
        <div class="error-list">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit">Register</button>
    </form>
    <div class="login-link">
        Already have an account? <a href="login.php">Log In</a>
    </div>
</div>
</body>
</html>