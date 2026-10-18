<?php
session_start();

$is_logged_in = isset($_SESSION['user_id']);
$is_admin = !empty($_SESSION['is_admin']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// Redirect user to the shop page if they are already logged in
if ($is_logged_in && empty($_GET['preview'])) {
    header('Location: shop.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prime Cuts Butchery - Fresh Cold Cuts in Thika</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-red: #e92543;
            --text-dark: #111111;
            --text-muted: #888888;
            --bg-page: #ffffff;
            --border-color: #ebebeb;
            --banner-bg: #fff0f2;
            --banner-text: #e92543;
            --img-placeholder: #e2e2e2;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: var(--bg-page); color: var(--text-dark); line-height: 1.5; }

        /* Top Black Bar */
        .top-bar { background-color: #050505; color: #ffffff; padding: 8px 5%; font-size: 0.75rem; display: flex; align-items: center; gap: 15px; font-weight: 600; }
        .top-bar .highlight { color: var(--primary-red); }
        .top-bar .divider { color: #333; }
        .top-bar a { color: #ffffff; text-decoration: none; display: flex; align-items: center; gap: 6px; }

        /* Main Header */
        .header-main { background-color: #ffffff; padding: 15px 5%; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); gap: 20px; }
        
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-dark); }
        .brand-icon { width: 45px; height: 45px; border: 1px solid var(--border-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: var(--text-dark); }
        .brand-text h1 { font-size: 1.1rem; font-weight: 900; letter-spacing: 0.5px; margin: 0; text-transform: uppercase; }
        .brand-text span { font-size: 0.65rem; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; }

        /* Search Bar */
        .search-container { flex-grow: 1; max-width: 550px; position: relative; }
        .search-container input { width: 100%; padding: 12px 20px 12px 45px; border-radius: 25px; border: 1px solid var(--border-color); background: #ffffff; font-size: 0.9rem; outline: none; color: var(--text-dark); }
        .search-container input::placeholder { color: #aaaaaa; }
        .search-container i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #aaaaaa; }

        /* Header Actions */
        .header-actions { display: flex; align-items: center; gap: 15px; }
        
        .location-badge { display: flex; align-items: center; gap: 10px; border: 1px solid var(--border-color); border-radius: 25px; padding: 6px 18px; font-size: 0.75rem; background: #ffffff; }
        .location-badge i { color: var(--primary-red); font-size: 1.1rem; }
        .location-badge .loc-title { font-size: 0.6rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase; line-height: 1; margin-bottom: 2px; }
        .location-badge .loc-desc { font-weight: 700; color: var(--text-dark); line-height: 1; }
        
        .btn-login { background-color: #000000; color: #ffffff; padding: 10px 24px; border-radius: 25px; text-decoration: none; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; border: none; }
        .btn-icon { width: 42px; height: 42px; border-radius: 50%; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: var(--text-dark); text-decoration: none; background: #ffffff; font-size: 1.1rem; }

        /* Sub Navigation */
        .sub-nav { background: #ffffff; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; }
        .sub-nav-links { display: flex; gap: 30px; }
        .sub-nav-links a { text-decoration: none; color: var(--text-muted); font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .sub-nav-links a.active { color: var(--primary-red); }
        .sub-nav-tagline { color: var(--text-muted); font-weight: 700; font-size: 0.85rem; }

        /* Main Content Container */
        .container { max-width: 1400px; margin: 40px auto; padding: 0 5%; }

        /* Promo Banner */
        .promo-banner { background-color: var(--banner-bg); border-radius: 16px; padding: 40px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; border: 1px solid #ffe6e9; }
        .promo-text h2 { color: var(--banner-text); font-size: 2.2rem; font-weight: 900; margin-bottom: 8px; text-transform: uppercase; letter-spacing: -0.5px; }
        .promo-text p { color: #c84153; font-size: 1.1rem; font-weight: 500; }
        .promo-icon { font-size: 3.5rem; color: #ffaebb; font-weight: 300; }

        /* Product Grid - Flat Design */
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; }
        
        .card-flat { border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; background: #ffffff; text-decoration: none; display: flex; flex-direction: column; }
        
        .card-img { background-color: var(--img-placeholder); height: 260px; position: relative; display: flex; align-items: center; justify-content: center; color: #a0a0a0; font-size: 1.8rem; font-weight: 700; }
        
        .badge-hot { position: absolute; top: 15px; right: 15px; background: var(--primary-red); color: #ffffff; font-size: 0.7rem; font-weight: 900; padding: 6px 12px; border-radius: 6px; letter-spacing: 0.5px; text-transform: uppercase; }
        
        .card-body { padding: 20px; border-top: 1px solid var(--border-color); }
        .card-title { font-size: 1.1rem; font-weight: 900; color: var(--text-dark); margin-bottom: 4px; text-transform: uppercase; }
        .card-subtitle { font-size: 0.75rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }

        @media (max-width: 992px) {
            .search-container { display: none; }
            .location-badge { display: none; }
            .promo-banner { flex-direction: column; text-align: left; align-items: flex-start; gap: 20px; position: relative; }
            .promo-icon { position: absolute; right: 40px; top: 50%; transform: translateY(-50%); opacity: 0.3; }
        }
    </style>
</head>
<body>

<!-- Top Black Bar -->
<div class="top-bar">
    <span class="highlight">Fresh Cold Cuts</span>
    <span class="divider">|</span>
    <span><i class="fas fa-map-marker-alt" style="color:var(--primary-red); margin-right:4px;"></i> Thika Starehe</span>
    <span class="divider">|</span>
    <a href="tel:0718292272"><i class="fas fa-phone-alt highlight" style="margin-right:4px;"></i> 0718 292 272</a>
</div>

<!-- Main Header -->
<header class="header-main">
    <a href="index.php" class="brand">
        <div class="brand-icon"><i class="fas fa-drumstick-bite"></i></div>
        <div class="brand-text">
            <h1>PRIME CUTS BUTCHERY</h1>
            <span>THIKA STAREHE • FRESH COLD-CHAIN DELIVERY</span>
        </div>
    </a>

    <div class="search-container">
        <i class="fas fa-search"></i>
        <!-- Redirects to login to encourage sign up before viewing real catalog -->
        <input type="text" placeholder="Search prime beef, ribeye, organic chicken, lamb chops..." readonly onclick="window.location.href='login.php'">
    </div>

    <div class="header-actions">
        <div class="location-badge">
            <i class="fas fa-map-marker-alt"></i>
            <div>
                <div class="loc-title">DELIVER TO:</div>
                <div class="loc-desc">Starehe Estate, Ga...</div>
            </div>
        </div>
        
        <?php if ($is_admin): ?>
            <a href="admin.php" class="btn-login" style="background:#17a2b8;"><i class="fas fa-cog"></i></a>
        <?php endif; ?>

        <a href="login.php" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Login
        </a>
        <a href="login.php" class="btn-icon">
            <i class="fas fa-shopping-bag"></i>
        </a>
    </div>
</header>

<!-- Sub Navigation -->
<nav class="sub-nav">
    <div class="sub-nav-links">
        <a href="#" class="active"><i class="fas fa-bolt"></i> Offers</a>
        <a href="#"><i class="fas fa-phone-alt"></i> Contact</a>
        <a href="#"><i class="fas fa-info-circle"></i> About</a>
    </div>
    <div class="sub-nav-tagline">
        Natural Pasture-Raised Meat
    </div>
</nav>

<main class="container">
    
    <!-- Promo Banner -->
    <div class="promo-banner">
        <div class="promo-text">
            <h2>WEEKLY SPECIALS</h2>
            <p>Enjoy premium cuts at unbeatable prices. Limited stock available.</p>
        </div>
        <div class="promo-icon">
            <i class="far fa-clock"></i>
        </div>
    </div>

    <!-- Flat Product Grid Preview -->
    <div class="product-grid">
        
        <!-- Preview Item 1 -->
        <a href="login.php" class="card-flat">
            <div class="card-img">
                Image Lost
                <div class="badge-hot">HOT DEAL</div>
            </div>
            <div class="card-body">
                <div class="card-title">GOAT ARM</div>
                <div class="card-subtitle">GOAT</div>
            </div>
        </a>

        <!-- Preview Item 2 -->
        <a href="login.php" class="card-flat">
            <div class="card-img">
                Image Lost
                <div class="badge-hot">HOT DEAL</div>
            </div>
            <div class="card-body">
                <div class="card-title">GOAT RIBS</div>
                <div class="card-subtitle">GOAT</div>
            </div>
        </a>

        <!-- Preview Item 3 -->
        <a href="login.php" class="card-flat">
            <div class="card-img">
                Image Lost
            </div>
            <div class="card-body">
                <div class="card-title">BEEF BRISKET</div>
                <div class="card-subtitle">BEEF</div>
            </div>
        </a>
        
        <!-- Preview Item 4 -->
        <a href="login.php" class="card-flat">
            <div class="card-img">
                Image Lost
            </div>
            <div class="card-body">
                <div class="card-title">WHOLE CHICKEN</div>
                <div class="card-subtitle">POULTRY</div>
            </div>
        </a>

    </div>

</main>

</body>
</html>