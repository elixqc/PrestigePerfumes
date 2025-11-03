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
            <!-- Mobile Toggle -->
            <button class="menu-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
            </button>

            <!-- Logo -->
            <a href="/prestigeperfumes/admin/dashboard.php" class="logo">
                <span class="logo-text">PRESTIGE ADMIN</span>
            </a>

            <!-- Navigation Links (centered) -->
            <ul class="nav-links">
                
                <li><a href="/prestigeperfumes/admin/additems.php">Add Products</a></li>
                <li><a href="/prestigeperfumes/admin/manageproducts.php">Manage Products</a></li>
                <li><a href="/prestigeperfumes/admin/suppliers.php">Suppliers</a></li>
                <li><a href="/prestigeperfumes/admin/orders.php">Orders</a></li>
                <li><a href="/prestigeperfumes/admin/customers.php">Customers</a></li>
                <li><a href="/prestigeperfumes/admin/adminaccounts.php">Admin Accounts</a></li>
                
            </ul> 

            <!-- Right Icons -->
            <div class="nav-icons">
                <div class="icon-link">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <circle cx="10" cy="7" r="3.5" stroke="currentColor"/>
                        <path d="M4 18c0-3.5 2.5-6 6-6s6 2.5 6 6" stroke="currentColor"/>
                    </svg>
                    <?php if (isset($_SESSION['admin_username'])): ?>
                        <span style="margin-left: 5px;"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
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
/* ========================================
   ADMIN HEADER – MATCH MAIN HEADER STYLE
======================================== */
.header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    background: #ffffff;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    z-index: 1000;
    transition: background 0.3s ease;
}

.navbar {
    padding: 0;
}

.nav-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 20px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 40px;
}

/* Logo */
.logo {
    text-decoration: none;
    color: #0a0a0a;
    font-size: 16px;
    letter-spacing: 4px;
    font-weight: 300;
    transition: opacity 0.3s ease;
    flex-shrink: 0;
}

.logo:hover {
    opacity: 0.7;
}

.logo-text {
    display: block;
}

/* Navigation Links */
.nav-links {
    display: flex;
    list-style: none;
    gap: 50px;
    margin: 0;
    padding: 0;
    flex: 1;
    justify-content: center;
}

.nav-links li {
    margin: 0;
}

.nav-links a {
    color: rgba(0, 0, 0, 0.7);
    text-decoration: none;
    font-size: 13px;
    letter-spacing: 2px;
    text-transform: uppercase;
    font-weight: 300;
    transition: color 0.3s ease;
    position: relative;
}

.nav-links a::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 0;
    height: 1px;
    background: #0a0a0a;
    transition: width 0.3s ease;
}

.nav-links a:hover,
.nav-links a.active {
    color: #0a0a0a;
}

.nav-links a:hover::after {
    width: 100%;
}

/* Navigation Icons */
.nav-icons {
    display: flex;
    gap: 25px;
    align-items: center;
    flex-shrink: 0;
}

.icon-link {
    color: rgba(0, 0, 0, 0.7);
    transition: color 0.3s ease;
    position: relative;
    display: flex;
    align-items: center;
}

.icon-link:hover {
    color: #0a0a0a;
}

/* Mobile Menu Toggle */
.menu-toggle {
    display: none;
    flex-direction: column;
    gap: 5px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
}

.menu-toggle span {
    width: 22px;
    height: 1px;
    background: #0a0a0a;
    transition: all 0.3s ease;
}

/* Main Content Offset */
main {
    margin-top: 0 !important;
    padding-top: 100px !important;
}

/* Responsive Navigation */
@media (max-width: 968px) {
    .nav-links {
        display: none;
        flex-direction: column;
        background: #ffffff;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        padding: 20px 0;
        text-align: center;
    }
    .menu-toggle {
        display: flex;
    }
}
</style>

<main class="admin-main">
