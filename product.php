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
    width: 100vw;
    min-height: 85vh;
    background: #f5f5f5;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 0;
}
.lux-product-container {
    display: flex;
    flex-direction: row;
    gap: 4rem;
    background: #fff;
    box-shadow: 0 2px 32px rgba(0,0,0,0.07);
    padding: 4rem 5vw;
    width: 100vw;
    max-width: 1600px;
    align-items: flex-start;
    border-radius: 0;
}
.lux-image-col {
    flex: 1.3;
    display: flex;
    justify-content: center;
    align-items: center;
}
.lux-product-image {
    width: 600px;
    height: 600px;
    object-fit: cover;
    background: #f2f2f2;
    box-shadow: 0 2px 24px rgba(0,0,0,0.09);
    border-radius: 0;
}
.lux-info-col {
    flex: 1.7;
    display: flex;
    flex-direction: column;
    gap: 2.5rem;
    justify-content: flex-start;
}
.lux-title {
    font-size: 3.2rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    margin-bottom: 0.5rem;
    font-family: 'Playfair Display', serif;
}
.lux-meta {
    font-size: 1.2rem;
    color: #888;
    margin-bottom: 0.5rem;
    font-family: 'Montserrat', sans-serif;
}
.lux-category {
    font-weight: 500;
}
.lux-variant {
    color: #aaa;
    margin-left: 0.5rem;
}
.lux-description {
    font-size: 1.25rem;
    color: #444;
    margin-bottom: 0.5rem;
    line-height: 1.7;
    font-family: 'Montserrat', sans-serif;
}
.lux-price-row {
    display: flex;
    gap: 2.5rem;
    align-items: center;
    font-size: 1.7rem;
    font-weight: 600;
    color: #222;
    margin-bottom: 1rem;
}
.lux-price {
    font-size: 2.2rem;
    color: #222;
    font-family: 'Playfair Display', serif;
}
.lux-stock {
    font-size: 1.2rem;
    color: #888;
    font-weight: 400;
    font-family: 'Montserrat', sans-serif;
}
.lux-cart-form {
    display: flex;
    gap: 2.5rem;
    align-items: center;
    margin-bottom: 1rem;
}
.lux-qty-row {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.lux-qty-label {
    font-size: 1.1rem;
    color: #222;
    font-family: 'Montserrat', sans-serif;
}
.lux-qty-input {
    width: 90px;
    padding: 0.6rem;
    font-size: 1.2rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #fafafa;
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
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    font-size: 1rem;
    text-align: center;
}
.alert-success {
    background: #e6f7ee;
    color: #1a7f37;
    border: 1px solid #b7e4c7;
}
.alert-error {
    background: #fbeaea;
    color: #c0392b;
    border: 1px solid #f5c6cb;
}
@media (max-width: 1100px) {
    .lux-product-container {
        flex-direction: column;
        align-items: center;
        padding: 2.5rem 2vw;
    }
    .lux-product-image {
        width: 350px;
        height: 350px;
    }
}
@media (max-width: 700px) {
    .lux-product-container {
        padding: 1.2rem 0.5vw;
    }
    .lux-product-image {
        width: 180px;
        height: 180px;
    }
    .lux-title {
        font-size: 2rem;
    }
    .lux-price {
        font-size: 1.2rem;
    }
}
<style>
/* Remove custom .lux-btn-cart styles, use .btn and .btn-primary from style.css */
.lux-btn-cart, .lux-btn-cart:hover, .lux-btn-cart:focus {
    all: unset;
}
.lux-product-detail {
