<?php
// Prestige Perfumery - Header
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/prestigeperfumes/includes/config.php');

// Initialize cart count
$cart_count = 0;
if (isset($_SESSION['customer_id'])) {
    $customer_id = (int) $_SESSION['customer_id'];
    $stmt = $conn->prepare("SELECT SUM(quantity) AS total_items FROM cart WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $cart_count = (int) ($result['total_items'] ?? 0);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prestige Perfumery</title>
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
                <li><a href="/prestigeperfumes/items/cart.php">Cart</a></li>
            </ul>

            <div class="nav-icons">
                <!-- Search Icon -->
                <a href="#" class="icon-link" aria-label="Search">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <circle cx="8" cy="8" r="6.5" stroke="currentColor"/>
                        <line x1="12.5" y1="12.5" x2="17" y2="17" stroke="currentColor"/>
                    </svg>
                </a>

                <!-- Account Icon -->
                <?php
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

                <!-- Notification Icon -->
                <div class="notif-wrapper">
                    <a href="#" class="icon-link notification-icon" aria-label="Notifications">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M12 22c1.1 0 2-.9 2-2h-4a2 2 0 002 2zm6-6V11c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 00-3 0v.68C7.63 5.36 6 7.92 6 11v5l-1.29 1.29A1 1 0 006 19h12a1 1 0 00.71-1.71L18 16z" stroke="currentColor" stroke-width="1.3"/>
                        </svg>
                        <span class="notif-count">0</span>
                    </a>

                    <!-- Dropdown -->
                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">Notifications</div>
                        <div class="notif-list" id="notifList"></div>
                    </div>
                </div>

                <!-- Cart Icon -->
                <a href="/prestigeperfumes/items/cart.php" class="icon-link cart-icon" aria-label="Cart">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <rect x="3" y="6" width="14" height="11" rx="1" stroke="currentColor"/>
                        <path d="M6 6V5a4 4 0 0 1 8 0v1" stroke="currentColor"/>
                    </svg>
                    <span class="cart-count"><?php echo $cart_count; ?></span>
                </a>
            </div>
        </div>
    </nav>
</header>

<style>
/* --- Notification Dropdown --- */
.notif-wrapper {
    position: relative;
}

.notification-icon {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.notification-icon:hover {
    transform: scale(1.05);
}

.notif-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #0a0a0a;
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    width: 17px;
    height: 17px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Dropdown container */
.notif-dropdown {
    position: absolute;
    top: 45px;
    right: -5px;
    width: 300px;
    background: #fff;
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 10px;
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    z-index: 1000;
    opacity: 0;
    transform: translateY(-10px);
    pointer-events: none;
    transition: all 0.25s ease;
}

.notif-dropdown.active {
    opacity: 1;
    transform: translateY(0);
    pointer-events: all;
}

.notif-header {
    background: #0a0a0a;
    color: #fff;
    text-align: center;
    font-size: 13px;
    letter-spacing: 1px;
    padding: 10px;
    font-family: 'Playfair Display', serif;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.notif-list {
    max-height: 300px;
    overflow-y: auto;
}

.notif-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 16px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    transition: background 0.3s ease;
}

.notif-item:hover {
    background: #f7f7f7;
}

.notif-item.unread {
    border-left: 3px solid #0a0a0a;
    background: #f9f9f9;
}

.notif-icon {
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 1px solid rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
}

.notif-content {
    flex: 1;
    font-family: 'Lato', sans-serif;
}

.notif-message {
    font-size: 13px;
    color: #111;
    line-height: 1.4;
}

.notif-time {
    font-size: 11px;
    color: #666;
    margin-top: 3px;
}

.no-notif {
    text-align: center;
    padding: 20px;
    font-size: 13px;
    color: #777;
    background: #fafafa;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const notifIcon = document.querySelector(".notification-icon");
    const notifDropdown = document.getElementById("notifDropdown");
    const notifCount = document.querySelector(".notif-count");
    const notifList = document.getElementById("notifList");

    notifIcon.addEventListener("click", function(e) {
        e.preventDefault();
        notifDropdown.classList.toggle("active");
    });

    fetch("/prestigeperfumes/user/notification.php")
        .then(res => res.json())
        .then(data => {
            notifList.innerHTML = "";

            if (data.length === 0) {
                notifCount.textContent = "0";
                notifList.innerHTML = "<div class='no-notif'>No notifications</div>";
                return;
            }

            notifCount.textContent = data.filter(n => n.is_read == 0).length;

            data.forEach(n => {
                const item = document.createElement("div");
                item.className = "notif-item" + (n.is_read == 0 ? " unread" : "");
                item.innerHTML = `
                    <div class="notif-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                            <path d="M12 22c1.1 0 2-.9 2-2h-4a2 2 0 002 2zm6-6V11c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 00-3 0v.68C7.63 5.36 6 7.92 6 11v5l-1.3 1.3A1 1 0 006 19h12a1 1 0 00.7-1.7L18 16z" stroke="#0a0a0a" stroke-width="1.2"/>
                        </svg>
                    </div>
                    <div class="notif-content">
                        <div class="notif-message">${n.message}</div>
                        <div class="notif-time">${n.created_at || ''}</div>
                    </div>
                `;
                notifList.appendChild(item);
            });
        })
        .catch(err => console.error("Error loading notifications:", err));

    document.addEventListener("click", function(e) {
        if (!notifIcon.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.remove("active");
        }
    });
});
</script>

<main>
