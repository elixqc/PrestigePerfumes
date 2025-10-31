<?php
// Prestige Perfumery - Admin Header
session_start();
include('../includes/config.php');

// Security check for admin login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../admin/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Prestige Perfumery</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
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
            
            <a href="/prestigeperfumes/admin/dashboard.php" class="logo">
                <span class="logo-text">PRESTIGE ADMIN</span>
            </a>
            
            <ul class="nav-links">
                <li><a href="/prestigeperfumes/admin/dashboard.php">Dashboard</a></li>
                <li><a href="/prestigeperfumes/admin/additems.php">Add Products</a></li>
                <li><a href="/prestigeperfumes/admin/manageproducts.php">Manage Products</a></li>
                <li><a href="/prestigeperfumes/admin/suppliers.php">Suppliers</a></li>
                <li><a href="/prestigeperfumes/admin/orders.php">Orders</a></li>
                <li><a href="/prestigeperfumes/admin/reports.php">Reports</a></li>
            </ul>

            <div class="nav-icons">
                <div class="icon-link">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <circle cx="10" cy="7" r="3.5" stroke="currentColor"/>
                        <path d="M4 18c0-3.5 2.5-6 6-6s6 2.5 6 6" stroke="currentColor"/>
                    </svg>
                    <?php if (isset($_SESSION['admin_username'])): ?>
                        <span style="margin-left: 5px; color: #fff;"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <?php endif; ?>
                </div>
                <a href="/prestigeperfumes/admin/logout.php" class="icon-link" aria-label="Logout">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M7 19H3a2 2 0 01-2-2V3a2 2 0 012-2h4M19 10l-4-4m4 4l-4 4m4-4H7" stroke="currentColor"/>
                    </svg>
                </a>
            </div>
        </div>
    </nav>
</header>

<style>
.header {
    background: #0a0a0a;
}

.logo-text {
    color: #ffffff;
}

.nav-links a {
    color: rgba(255, 255, 255, 0.8);
}

.nav-links a:hover {
    color: #c5a253;
}

.nav-links a::after {
    background: #c5a253;
}

.admin-main {
    padding: 40px;
    margin-top: 80px;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
}

.icon-link {
    color: rgba(255, 255, 255, 0.8);
}

.icon-link:hover {
    color: #c5a253;
}

@media (max-width: 968px) {
    .nav-links {
        background: #0a0a0a;
    }
}
</style>

<main class="admin-main">
