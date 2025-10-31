<?php
// /items/index.php
require_once('../includes/config.php');
require_once('../includes/header.php');

if (!isset($conn)) {
    die("Database connection not established. Please check config.php");
}
?>

<!-- Hero Section -->
<section class="hero hero-compact">
    <div class="hero-overlay">
        <div class="hero-content">
            <h1>Our Signature Collection</h1>
            <p>Explore refined scents crafted for timeless elegance.</p>
            <a href="#collections" class="btn btn-primary">Browse the Collection</a>
        </div>
    </div>
</section>


<!-- Collections Section -->
<section class="featured-products" id="collections">
    <div class="container">
        <h2>All Collections</h2>
        <div class="product-grid">
            <?php
            try {
                $sql = "SELECT product_id, product_name, price, image_path 
                        FROM products 
                        WHERE is_active = 1 
                        ORDER BY product_id DESC";
                $stmt = $conn->prepare($sql);

                if (!$stmt) {
                    throw new Exception("Failed to prepare statement: " . $conn->error);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Failed to execute query: " . $stmt->error);
                }

                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $productId = htmlspecialchars($row['product_id']);
                        $productName = htmlspecialchars($row['product_name']);
                        $imagePath = htmlspecialchars($row['image_path']);
                        $price = number_format($row['price'], 2);
                        ?>
                        <div class="product-card">
                                <a href="../product.php?id=<?php echo $productId; ?>" class="product-link">
                                <img src="<?php echo '../' . ltrim($imagePath, '/'); ?>" 
                                    alt="<?php echo $productName; ?>" 
                                    loading="lazy">

                                <div class="product-info">
                                    <h3><?php echo $productName; ?></h3>
                                    <p class="price">₱<?php echo $price; ?></p>
                                </div>
                            </a>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p class="text-center">No products available at the moment.</p>';
                }

                $stmt->close();
            } catch (Exception $e) {
                echo '<p class="text-center">Unable to load collections. Please try again later.</p>';
                error_log("Error loading collections: " . $e->getMessage());
            }
            ?>
        </div>
    </div>
</section>

<!-- Newsletter -->
<section class="newsletter">
    <div class="container">
        <h2>Stay Updated</h2>
        <p>Subscribe to our newsletter to get the latest scent releases and special offers.</p>
        <form action="../subscribe.php" method="post" class="newsletter-form">
            <input type="email" name="email" placeholder="Enter your email address" required>
            <button type="submit" class="btn btn-primary">Subscribe</button>
        </form>
    </div>
</section>

<?php
require_once('../includes/footer.php');
?>

<style>
    .hero-compact {
    height: 60vh; /* shorter than full screen */
    background: url('../assets/images/hero-perfume.jpg') center/cover no-repeat;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 20px;
}

.hero-compact .hero-overlay {
    background: rgba(0, 0, 0, 0.45); /* slightly lighter overlay */
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.hero-compact .hero-content h1 {
    font-size: 3rem;
    font-weight: 200;
    color: #ffffff;
    margin-bottom: 10px;
    font-family: 'Playfair Display', serif;
}

.hero-compact .hero-content p {
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 25px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.hero-compact .btn.btn-primary {
    padding: 14px 40px;
    letter-spacing: 2px;
    font-size: 11px;
}

    </style>
