</main>

<footer class="footer">
    <div class="footer-content">
        <div class="footer-grid">
            <!-- Brand Column -->
            <div class="footer-column">
                <h3 class="footer-logo">PRESTIGE</h3>
                <p class="footer-tagline">Crafting timeless fragrances for the distinguished</p>
                <div class="footer-social">
                    <a href="#" aria-label="Instagram">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                        </svg>
                    </a>
                    <a href="#" aria-label="Facebook">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                        </svg>
                    </a>
                    <a href="#" aria-label="Twitter">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Collections Column -->
            <div class="footer-column">
                <h4 class="footer-title">Collections</h4>
                <ul class="footer-links">
                    <li><a href="/prestigeperfumes/items/index.php">All Fragrances</a></li>
                    <?php
                    // Fetch categories dynamically
                    $cat_query = "SELECT category_id, category_name FROM categories ORDER BY category_id";
                    $cat_result = $conn->query($cat_query);
                    if ($cat_result && $cat_result->num_rows > 0) {
                        while ($cat = $cat_result->fetch_assoc()) {
                            echo '<li><a href="/prestigeperfumes/items/index.php?category=' . $cat['category_id'] . '">' . htmlspecialchars($cat['category_name']) . '</a></li>';
                        }
                    }
                    ?>
                </ul>
            </div>

            <!-- Information Column -->
            <div class="footer-column">
                <h4 class="footer-title">Information</h4>
                <ul class="footer-links">
                    <li><a href="/prestigeperfumes/about.php">About Maison</a></li>
                    <li><a href="/prestigeperfumes/contact.php">Contact Us</a></li>
                    <li><a href="/prestigeperfumes/shipping.php">Shipping & Delivery</a></li>
                    <li><a href="/prestigeperfumes/faq.php">FAQ</a></li>
                </ul>
            </div>

            <!-- Account Column -->
            <div class="footer-column">
                <h4 class="footer-title">My Account</h4>
                <ul class="footer-links">
                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <li><a href="/prestigeperfumes/user/index.php">My Profile</a></li>
                        <li><a href="/prestigeperfumes/user/orders.php">Order History</a></li>
                        <li><a href="/prestigeperfumes/items/cart.php">Shopping Cart</a></li>
                        <li><a href="/prestigeperfumes/user/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="/prestigeperfumes/user/login.php">Login</a></li>
                        <li><a href="/prestigeperfumes/user/register.php">Register</a></li>
                        <li><a href="/prestigeperfumes/items/cart.php">Shopping Cart</a></li>
                        <li><a href="/prestigeperfumes/items/index.php">Browse Products</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> Prestige Perfumery. All Rights Reserved.</p>
            <div class="footer-legal">
                <a href="/prestigeperfumes/privacy.php">Privacy Policy</a>
                <span class="separator">|</span>
                <a href="/prestigeperfumes/terms.php">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<style>
.footer {
    background: #0a0a0a;
    color: #fff;
    padding: 60px 0 30px;
    margin-top: 80px;
    font-family: 'Lato', sans-serif;
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 40px;
    margin-bottom: 40px;
}

.footer-column {
    min-width: 0;
}

.footer-logo {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 700;
    letter-spacing: 3px;
    margin-bottom: 12px;
    color: #fff;
}

.footer-tagline {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.6);
    line-height: 1.6;
    margin-bottom: 20px;
    font-style: italic;
}

.footer-social {
    display: flex;
    gap: 15px;
}

.footer-social a {
    width: 36px;
    height: 36px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255, 255, 255, 0.7);
    transition: all 0.3s ease;
}

.footer-social a:hover {
    background: #fff;
    color: #0a0a0a;
    border-color: #fff;
    transform: translateY(-2px);
}

.footer-title {
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 20px;
    color: #fff;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.6);
    font-size: 14px;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-block;
}

.footer-links a:hover {
    color: #fff;
    padding-left: 5px;
}

.footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.footer-bottom p {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.5);
    margin: 0;
}

.footer-legal {
    display: flex;
    gap: 15px;
    align-items: center;
}

.footer-legal a {
    color: rgba(255, 255, 255, 0.5);
    font-size: 12px;
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-legal a:hover {
    color: #fff;
}

.footer-legal .separator {
    color: rgba(255, 255, 255, 0.3);
    font-size: 12px;
}

@media (max-width: 768px) {
    .footer {
        padding: 40px 0 20px;
        margin-top: 60px;
    }

    .footer-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
    }

    .footer-bottom {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
}

@media (max-width: 480px) {
    .footer-grid {
        grid-template-columns: 1fr;
    }
}
</style>

</body>
</html>