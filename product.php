<?php
require_once('includes/config.php');
require_once('includes/header.php');

// Get product id
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = null;
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT p.*, c.category_name, s.supplier_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id WHERE p.product_id = ? AND p.is_active = 1");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$product) {
    echo '<div class="container"><h2>Product not found.</h2></div>';
    require_once('includes/footer.php');
    exit();
}
?>

<section class="lux-product-detail">
    <div class="lux-product-container">
        <div class="lux-image-col">
            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="lux-product-image" />
        </div>
        <div class="lux-info-col">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <h1 class="lux-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>
            <div class="lux-meta">
                <span class="lux-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                <?php if ($product['variant']): ?>
                    <span class="lux-variant">| <?php echo htmlspecialchars($product['variant']); ?></span>
                <?php endif; ?>
            </div>
            <p class="lux-description"><?php echo htmlspecialchars($product['description']); ?></p>
            <div class="lux-price-row">
                <span class="lux-price">₱<?php echo number_format($product['price'], 2); ?></span>
                <span class="lux-stock">Stock: <?php echo $product['stock_quantity']; ?></span>
            </div>
            <form id="add-to-cart-form" method="POST" action="items/add_to_cart.php" class="lux-cart-form">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>" />
                <div class="lux-qty-row">
                    <label for="quantity" class="lux-qty-label">Quantity</label>
                    <input type="number" name="quantity" id="quantity" min="1" max="<?php echo $product['stock_quantity']; ?>" value="1" class="lux-qty-input" required />
                </div>
                <button type="submit" class="btn btn-primary">Add to Cart</button>
            </form>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="featured-section">
    <div class="featured-container">
        <h2>You May Also Like</h2>
        <div class="featured-grid">
            <?php
            // Get featured products excluding current product
            try {
                $sql = "SELECT product_id, product_name, price, image_path 
                        FROM products 
                        WHERE is_active = 1 
                        AND product_id != ?
                        AND category_id = ?
                        ORDER BY RAND()
                        LIMIT 4";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $product_id, $product['category_id']);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $featuredId = htmlspecialchars($row['product_id']);
                        $featuredName = htmlspecialchars($row['product_name']);
                        $featuredImage = htmlspecialchars($row['image_path']);
                        $featuredPrice = number_format($row['price'], 2);
                        ?>
                        <div class="featured-card">
                            <a href="product.php?id=<?php echo $featuredId; ?>" class="product-link">
                                <img src="<?php echo $featuredImage; ?>" 
                                     alt="<?php echo $featuredName; ?>" 
                                     loading="lazy">
                                <div class="featured-info">
                                    <h3><?php echo $featuredName; ?></h3>
                                    <p class="price">₱<?php echo $featuredPrice; ?></p>
                                </div>
                            </a>
                        </div>
                        <?php
                    }
                }
                $stmt->close();
            } catch (Exception $e) {
                // Silently log error without disrupting the page
                error_log("Error loading featured products: " . $e->getMessage());
            }
            ?>
        </div>
    </div>
</section>

<style>
.btn.btn-primary {
    background: #0a0a0a !important;
    color: #fff !important;
    border: 1px solid #0a0a0a !important;
}
.btn.btn-primary::before {
    background: #bfa46b !important;
}
.btn.btn-primary:hover {
    color: #0a0a0a !important;
    border-color: #bfa46b !important;
    background: #bfa46b !important;
}
.lux-product-detail {
    width: 100%;
    min-height: 85vh;
    background: #ffffff;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 80px 0;
}
.lux-product-container {
    display: flex;
    flex-direction: row;
    gap: 8rem;
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
    align-items: flex-start;
}
.lux-image-col {
    flex: 1;
    display: flex;
    justify-content: flex-start;
    align-items: flex-start;
}
.lux-product-image {
    width: 100%;
    height: auto;
    aspect-ratio: 1;
    object-fit: cover;
    background: #ffffff;
}
.lux-info-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2rem;
    justify-content: flex-start;
}
.lux-title {
    font-size: 42px;
    font-weight: 200;
    letter-spacing: 1px;
    margin-bottom: 0.5rem;
    font-family: 'Playfair Display', serif;
    color: #0a0a0a;
}
.lux-meta {
    font-size: 13px;
    color: rgba(0, 0, 0, 0.7);
    margin-bottom: 0.5rem;
    font-family: 'Lato', sans-serif;
    letter-spacing: 2px;
    text-transform: uppercase;
}
.lux-category {
    font-weight: 300;
}
.lux-variant {
    color: rgba(0, 0, 0, 0.5);
    margin-left: 0.5rem;
}
.lux-description {
    font-size: 16px;
    color: #666;
    margin-bottom: 0.5rem;
    line-height: 1.8;
    font-family: 'Lato', sans-serif;
    font-weight: 300;
}
.lux-price-row {
    display: flex;
    gap: 3rem;
    align-items: center;
    margin-bottom: 2rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}
.lux-price {
    font-size: 24px;
    color: #0a0a0a;
    font-family: 'Playfair Display', serif;
    font-weight: 300;
}
.lux-stock {
    font-size: 13px;
    color: rgba(0, 0, 0, 0.7);
    font-weight: 300;
    font-family: 'Lato', sans-serif;
    letter-spacing: 1px;
}
.lux-cart-form {
    display: flex;
    gap: 2rem;
    align-items: flex-end;
    margin-bottom: 1rem;
}
.lux-qty-row {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.lux-qty-label {
    font-size: 12px;
    color: #0a0a0a;
    font-family: 'Lato', sans-serif;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 400;
}
.lux-qty-input {
    width: 100px;
    padding: 14px 18px;
    font-size: 14px;
    border: 1px solid rgba(0, 0, 0, 0.15);
    background: #ffffff;
    font-family: 'Lato', sans-serif;
    font-weight: 300;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}
.lux-qty-input:focus {
    outline: none;
    border-color: #0a0a0a;
}
.lux-btn-cart {
    background: linear-gradient(90deg, #222 0%, #444 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 1.1rem 3rem;
    font-size: 1.3rem;
    font-family: 'Montserrat', sans-serif;
    cursor: pointer;
    transition: background 0.25s, box-shadow 0.25s;
    font-weight: 700;
    letter-spacing: 0.04em;
    box-shadow: 0 2px 8px rgba(34,34,34,0.08);
}
.lux-btn-cart:hover {
    background: linear-gradient(90deg, #bfa46b 0%, #222 100%);
    color: #fff;
    box-shadow: 0 4px 16px rgba(191,164,107,0.15);
}
.alert {
    padding: 15px 20px;
    margin-bottom: 25px;
    border-radius: 0;
    font-size: 13px;
    letter-spacing: 0.5px;
    font-weight: 300;
    text-align: center;
}
.alert-success {
    background: rgba(40, 167, 69, 0.1);
    border: 1px solid rgba(40, 167, 69, 0.3);
    color: #28a745;
}
.alert-error {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: #c82333;
}
@media (max-width: 1100px) {
    .lux-product-container {
        gap: 4rem;
    }
}
@media (max-width: 968px) {
    .lux-product-container {
        flex-direction: column;
        gap: 3rem;
    }
    .lux-image-col {
        width: 100%;
    }
    .lux-product-image {
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
    }
    .lux-title {
        font-size: 32px;
    }
}
@media (max-width: 480px) {
    .lux-product-container {
        padding: 0 20px;
    }
    .lux-title {
        font-size: 28px;
    }
    .lux-cart-form {
        flex-direction: column;
        gap: 1.5rem;
        align-items: stretch;
    }
    .lux-qty-input {
        width: 100%;
    }
}

/* Featured Products Section */
.featured-section {
    padding: 40px 0;
    background: #ffffff;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.featured-section h2 {
    font-size: 36px;
    font-weight: 200;
    letter-spacing: 1px;
    text-align: center;
    margin-bottom: 40px;
    color: #0a0a0a;
    font-family: 'Playfair Display', serif;
}

.featured-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
}

.featured-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 40px;
}

.featured-card {
    background: #ffffff;
    transition: transform 0.4s ease;
}

.featured-card:hover {
    transform: translateY(-10px);
}

.featured-card a {
    text-decoration: none;
    color: inherit;
    display: block;
}

.featured-card img {
    width: 100%;
    height: 350px;
    object-fit: cover;
    display: block;
    filter: grayscale(20%);
    transition: filter 0.4s ease;
}

.featured-card:hover img {
    filter: grayscale(0%);
}

.featured-info {
    padding: 25px 0;
    text-align: center;
}

.featured-info h3 {
    font-size: 16px;
    font-weight: 400;
    letter-spacing: 1px;
    margin-bottom: 10px;
    color: #0a0a0a;
    font-family: 'Lato', sans-serif;
}

.featured-info .price {
    font-size: 14px;
    color: #666;
    font-weight: 300;
}

@media (max-width: 768px) {
    .featured-section {
        padding: 60px 0;
    }
    
    .featured-section h2 {
        font-size: 32px;
        margin-bottom: 40px;
    }
    
    .featured-container {
        padding: 0 20px;
    }
    
    .featured-grid {
        gap: 30px;
    }
}

<style>
/* Remove custom .lux-btn-cart styles, use .btn and .btn-primary from style.css */
.lux-btn-cart, .lux-btn-cart:hover, .lux-btn-cart:focus {
    all: unset;
}
.lux-product-detail {
