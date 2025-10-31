<?php
require_once('includes/config.php');
require_once('includes/header.php');

if (!isset($conn)) {
    die("Database connection not established. Please check config.php");
}



?>

<section class="hero">
    <div class="hero-overlay">
        <div class="hero-content">
            <h1>Discover Elegance</h1>
            <p>Premium fragrances curated for the discerning nose.</p>
            <a href="shop.php" class="btn btn-primary">Shop Now</a>
        </div>
    </div>
</section>

<section class="featured-products">
    <div class="container">
        <h2>Featured Collection</h2>
        <div class="product-grid">
            <?php
            try {
                $sql = "SELECT product_id, product_name, price, image_path 
                        FROM products 
                        WHERE is_active = 1 
                        ORDER BY product_id DESC 
                        LIMIT 4";

                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
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
                                <a href="product.php?id=<?php echo $productId; ?>" class="product-link">
                                <img src="<?php echo $imagePath; ?>" 
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
                    echo '<p class="text-center">No featured products available at the moment.</p>';
                }
                
                $stmt->close();
            } catch (Exception $e) {
                echo '<p class="text-center">Unable to load featured products. Please try again later.</p>';
                error_log("Error in featured products: " . $e->getMessage());
            }
            ?>
        </div>
    </div>
</section>


<section class="about-brand">
    <div class="container">
        <div class="about-inner">
            <div class="about-text">
                <h2 class="about-title">The Essence of Elegance</h2>
                <p class="about-description">
                    At Prestige Perfumery, we curate fragrances that transcend time. Each bottle tells a story of sophistication and refined artistry, crafted for those who understand that scent is an expression of self.
                </p>
                <a href="about.php" class="btn btn-gold">Explore Our Story</a>
            </div>
            <div class="about-image">
                <img src="assets/images/about-perfume.png" alt="Prestige Perfumery" loading="lazy">
            </div>
        </div>
    </div>
</section>


<section class="newsletter">
    <div class="container">
        <h2>Stay Informed</h2>
        <p>Join our mailing list for exclusive drops, offers and scent stories.</p>
        <form action="subscribe.php" method="post" class="newsletter-form">
            <input type="email" name="email" placeholder="Enter your email address" required>
            <button type="submit" class="btn btn-primary">Subscribe</button>
        </form>
    </div>
</section>

<?php
include('includes/footer.php');
?>
