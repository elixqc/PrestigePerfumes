<?php
// Prestige Perfumery - Header
session_start(); // Make sure session is started
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prestige Perfumery</title>
    <?php
    $cssPath = (strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '../assets/css/style.css' : 'assets/css/style.css';
    ?>
    <link rel="stylesheet" href="/prestigeperfumes/assets/css/style.css">
</head>
<body>
<header class="header">
    <nav class="navbar">
        <div class="nav-container">
            <button class="menu-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
            </button>
            
            <a href="/prestigeperfumes/index.php" class="logo">
                <span class="logo-text">PRESTIGE</span>
            </a>
            
            <ul class="nav-links">
                <li><a href="/prestigeperfumes/items/index.php">Collections</a></li>
                <li><a href="/prestigeperfumes/about.php">Maison</a></li>
                <li><a href="/prestigeperfumes/cart.php">Cart</a></li>
            </ul>

            <div class="nav-icons">
                <a href="#" class="icon-link" aria-label="Search">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <circle cx="8" cy="8" r="6.5" stroke="currentColor"/>
                        <line x1="12.5" y1="12.5" x2="17" y2="17" stroke="currentColor"/>
                    </svg>
                </a>

                <?php if (isset($_SESSION['customer_id'])): ?>
                    <!-- User Profile Link -->
                    <?php
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $accountLink = isset($_SESSION['customer_id'])
                    ? '/prestigeperfumes/user/index.php'
                    : '/prestigeperfumes/user/login.php';
                ?>
                <a href="<?php echo $accountLink; ?>" class="icon-link" aria-label="Account">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <circle cx="10" cy="7" r="3.5" stroke="currentColor"/>
                        <path d="M4 18c0-3.5 2.5-6 6-6s6 2.5 6 6" stroke="currentColor"/>
                    </svg>
                </a>

                <?php else: ?>
                    <!-- Login Link -->
                    <a href="/prestigeperfumes/user/login.php" class="icon-link" aria-label="Login">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <circle cx="10" cy="7" r="3.5" stroke="currentColor"/>
                            <path d="M4 18c0-3.5 2.5-6 6-6s6 2.5 6 6" stroke="currentColor"/>
                        </svg>
                    </a>
                <?php endif; ?>

                <a href="/prestigeperfumes/cart.php" class="icon-link cart-icon" aria-label="Cart">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <rect x="3" y="6" width="14" height="11" rx="1" stroke="currentColor"/>
                        <path d="M6 6V5a4 4 0 0 1 8 0v1" stroke="currentColor"/>
                    </svg>
                    <span class="cart-count">0</span>
                </a>
            </div>
        </div>
    </nav>
</header>

<main>
