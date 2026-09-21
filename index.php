<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prime Cuts Butchery - Portal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .portal-container {
            text-align: center;
            max-width: 800px;
            width: 90%;
        }
        .portal-header {
            margin-bottom: 2rem;
        }
        .portal-header h1 {
            color: #8b0000;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }
        .portal-header p {
            color: #555;
            font-size: 1.1rem;
        }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }
        .role-card {
            background: #fff;
            padding: 2.5rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-top: 5px solid #8b0000;
        }
        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        .role-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .role-card h2 {
            margin: 0 0 10px;
            color: #222;
        }
        .role-card p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.4;
            margin-bottom: 1.5rem;
        }
        .btn {
            display: inline-block;
            width: 80%;
            padding: 12px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1rem;
            transition: background 0.2s;
        }
        .btn-customer {
            background: #8b0000;
            color: white;
        }
        .btn-customer:hover {
            background: #660000;
        }
        .btn-admin {
            background: #343a40;
            color: white;
        }
        .btn-admin:hover {
            background: #1d2124;
        }
    </style>
</head>
<body>

<div class="portal-container">
    <div class="portal-header">
        <h1>Prime Cuts Butchery</h1>
        <p>Please select your portal to continue</p>
    </div>

    <div class="cards-grid">
        <!-- Customer Option -->
        <div class="role-card">
            <div class="role-icon">🥩</div>
            <h2>Customer Portal</h2>
            <p>Browse fresh meats, select cut preferences, place orders, and track delivery status.</p>
            <a href="login.php" class="btn btn-customer">Customer Sign In</a>
        </div>

        <!-- Admin Option -->
        <div class="role-card" style="border-top-color: #343a40;">
            <div class="role-icon">⚙️</div>
            <h2>Admin Portal</h2>
            <p>Manage butcher shop orders, update preparation status, view revenue, and update stock.</p>
            <a href="admin-login.php" class="btn btn-admin">Admin Sign In</a>
        </div>
    </div>
</div>

</body>
</html>