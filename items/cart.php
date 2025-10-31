<?php
// Prestige Perfumery - Cart Page
session_start();
require_once('../includes/config.php');

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: ../user/login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Handle update quantities
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    foreach ($_POST['quantities'] as $product_id => $qty) {
        $product_id = intval($product_id);
        $qty = intval($qty);
        if ($qty > 0) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE customer_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $qty, $customer_id, $product_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: cart.php");
    exit();
}

// Handle remove
if (isset($_GET['remove'])) {
    $product_id = intval($_GET['remove']);
    $stmt = $conn->prepare("DELETE FROM cart WHERE customer_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $customer_id, $product_id);
    $stmt->execute();
    $stmt->close();
    header("Location: cart.php");
    exit();
}

// Get cart items
$stmt = $conn->prepare("
    SELECT c.product_id, c.quantity, p.product_name, p.price, p.stock_quantity, p.image_path, p.variant
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.customer_id = ?
");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once('../includes/header.php');
?>

<!-- Load Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500&family=Playfair+Display:wght@200;400&display=swap" rel="stylesheet">

<section class="cart-section">
    <div class="cart-container">
        <h1 class="lux-title">Your Shopping Cart</h1>
        <?php if (count($cart_items) === 0): ?>
            <p class="empty-cart">Your cart is empty.</p>
        <?php else: ?>
            <form method="POST" action="cart.php">
                <?php 
                $total = 0;
                foreach ($cart_items as $item): 
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;
                ?>
                    <div class="cart-item">
                        <div class="cart-item-img">
                            <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" />
                        </div>
                        <div class="cart-item-info">
                            <h2 class="lux-title"><?php echo htmlspecialchars($item['product_name']); ?></h2>
                            <?php if($item['variant']): ?>
                                <p>Variant: <?php echo htmlspecialchars($item['variant']); ?></p>
                            <?php endif; ?>
                            <p>Price: ₱<?php echo number_format($item['price'],2); ?></p>
                            <p>Stock: <?php echo $item['stock_quantity']; ?></p>
                            <div class="cart-actions">
                                <input type="number" name="quantities[<?php echo $item['product_id']; ?>]" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" max="<?php echo $item['stock_quantity']; ?>" 
                                       class="lux-qty-input" />
                                <button type="submit" name="update" class="btn btn-primary"><span>Update</span></button>
                                <a href="cart.php?remove=<?php echo $item['product_id']; ?>" class="btn btn-primary"><span>Remove</span></a>
                            </div>
                            <p>Subtotal: ₱<?php echo number_format($subtotal,2); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="cart-total">
                    <h2>Total: ₱<?php echo number_format($total,2); ?></h2>
                    <a href="checkout.php" class="btn btn-primary"><span>Proceed to Checkout</span></a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<style>
.cart-section {
    padding: 80px 20px;
    min-height: 85vh;
    background: #ffffff;
}
.cart-container {
    max-width: 1200px;
    margin: 0 auto;
}
.cart-item {
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    padding-bottom: 2rem;
    align-items: flex-start;
}
.cart-item-img img {
    width: 200px;
    height: auto;
    object-fit: cover;
    border-radius: 10px;
}
.cart-item-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.cart-item-info h2 {
    font-family: 'Playfair Display', serif;
    font-weight: 400;
    margin-bottom: 0.5rem;
    font-size: 32px;
}
.cart-item-info p {
    font-family: 'Montserrat', sans-serif;
    font-weight: 300;
    margin: 0.2rem 0;
    font-size: 16px;
}
.cart-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin: 0.5rem 0;
}
.lux-qty-input {
    width: 80px;
    padding: 10px;
    border: 1px solid rgba(0,0,0,0.15);
    border-radius: 5px;
    font-family: 'Montserrat', sans-serif;
    font-weight: 300;
    font-size: 14px;
}

/* BUTTON DESIGN REFERENCE */
.btn {
    display: inline-block;
    padding: 18px 50px;
    text-decoration: none;
    font-size: 12px;
    letter-spacing: 2px;
    text-transform: uppercase;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 500;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    font-family: 'Montserrat', sans-serif;
}
.btn-primary {
    position: relative;
    background: transparent;
    color: #0a0a0a;
    border: 1px solid rgba(0,0,0,0.4);
    overflow: hidden;
}

.btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: #0a0a0a;
    transition: left 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 0;
}

.btn-primary span {
    position: relative;
    z-index: 1;
}

.btn-primary:hover::before {
    left: 0;
}

.btn-primary:hover {
    color: #ffffff;
}

.cart-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2rem;
    font-size: 28px;
    font-weight: 400;
    font-family: 'Playfair Display', serif;
}
.empty-cart {
    font-family: 'Montserrat', sans-serif;
    font-weight: 300;
    font-size: 18px;
    text-align: center;
    padding: 50px 0;
}
@media (max-width: 768px) {
    .cart-item {
        flex-direction: column;
        align-items: center;
    }
    .cart-item-img img {
        width: 100%;
        max-width: 300px;
    }
    .cart-actions {
        flex-direction: column;
        width: 100%;
        gap: 0.5rem;
    }
    .cart-total {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}
</style>

<?php require_once('../includes/footer.php'); ?>
