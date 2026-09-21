<?php
session_start();
require_once 'db.php';

$username = 'jane';
$password = 'mutheu7106';
$email    = 'jane@primecuts.com';

$success = false;
$message = '';

try {
    // Generate a fresh password hash compatible with password_verify()
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Remove any existing user with the same username or email to eliminate conflicting hashes
    $deleteStmt = $pdo->prepare('DELETE FROM users WHERE username = :username OR email = :email');
    $deleteStmt->execute([
        'username' => $username,
        'email'    => $email
    ]);

    // Insert the new admin user with active administrator privileges (is_admin = 1)
    $insertStmt = $pdo->prepare('INSERT INTO users (username, email, password, is_admin) VALUES (:username, :email, :password, 1)');
    $insertStmt->execute([
        'username' => $username,
        'email'    => $email,
        'password' => $hashed_password
    ]);

    $success = true;
    $message = "Admin account for '{$username}' has been successfully created!";
} catch (PDOException $e) {
    $success = false;
    $message = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin Account</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .card {
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            max-width: 420px;
            width: 100%;
            text-align: center;
        }
        .card h2 {
            margin-top: 0;
            color: #333;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            text-align: left;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .details {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 15px;
            border-radius: 5px;
            text-align: left;
            margin-bottom: 20px;
        }
        .details p {
            margin: 6px 0;
            font-size: 0.9rem;
        }
        .btn {
            display: inline-block;
            background-color: #8b0000;
            color: #ffffff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: background 0.2s;
        }
        .btn:hover {
            background-color: #660000;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>Admin Account Setup</h2>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>Success!</strong> <?= htmlspecialchars($message) ?>
        </div>

        <div class="details">
            <p><strong>Username:</strong> <?= htmlspecialchars($username) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
            <p><strong>Password:</strong> <?= htmlspecialchars($password) ?></p>
            <p><strong>Role:</strong> Administrator (<code>is_admin = 1</code>)</p>
        </div>

        <a href="login.php" class="btn">Proceed to Login Page</a>
    <?php else: ?>
        <div class="alert alert-danger">
            <strong>Error!</strong> <?= htmlspecialchars($message) ?>
        </div>
        <p style="font-size: 0.85rem; color: #666;">Verify that your database table <code>users</code> contains the columns <code>username</code>, <code>email</code>, <code>password</code>, and <code>is_admin</code>.</p>
    <?php endif; ?>
</div>

</body>
</html>