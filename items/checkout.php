<?php
// Prestige Perfumery - Checkout Page
session_start();
require_once('../includes/config.php');

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../user/login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Fetch customer info
$stmt = $conn->prepare("SELECT full_name, email, contact_number, address FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch cart items
$stmt = $conn->prepare("
    SELECT c.product_id, c.quantity, p.product_name, p.price, p.variant, p.image_path
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.customer_id = ?
");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Redirect if cart is empty
if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

require_once('../includes/header.php');
?>

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500&family=Playfair+Display:wght@400;600&display=swap" rel="stylesheet">

<section class="checkout-section">
    <div class="checkout-container">
        <h1 class="lux-title">Order Confirmation</h1>

        <form method="POST" action="confirm_checkout.php" class="checkout-form">
            <div class="checkout-grid">

                <!-- Delivery Information -->
                <div class="checkout-box">
                    <h2 class="box-title">Delivery Information</h2>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($customer['full_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" value="<?php echo htmlspecialchars($customer['contact_number']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Delivery Address</label>
                        <textarea name="address" rows="3" required><?php echo htmlspecialchars($customer['address']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Mode of Payment</label>
                        <select name="payment_method" required>
                            <option value="Cash on Delivery">Cash on Delivery</option>
                            <option value="GCash">GCash</option>
                            <option value="Credit Card">Credit Card</option>
                        </select>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="checkout-box">
                    <h2 class="box-title">Order Summary</h2>

                    <?php foreach ($cart_items as $item): ?>
                        <div class="summary-item">
                            <div class="summary-img">
                                <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            </div>
                            <div class="summary-info">
                                <h3><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                <?php if ($item['variant']): ?>
                                    <p>Variant: <?php echo htmlspecialchars($item['variant']); ?></p>
                                <?php endif; ?>
                                <p>Quantity: <?php echo $item['quantity']; ?></p>
                                <p>₱<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="summary-total">
                        <h3>Total:</h3>
                        <p>₱<?php echo number_format($total, 2); ?></p>
                    </div>
                </div>

            </div>

            <!-- Buttons -->
            <div class="checkout-buttons">
                <a href="cart.php" class="btn btn-outline"><span>Back to Cart</span></a>
                <button type="submit" class="btn btn-primary"><span>Confirm Order</span></button>
            </div>

            <input type="hidden" name="total_amount" value="<?php echo $total; ?>">
        </form>
    </div>
</section>

<?php require_once('../includes/footer.php'); ?>

<style>
.checkout-section {
    padding: 80px 20px;
    background: #ffffff;
    font-family: 'Montserrat', sans-serif;
}

.checkout-container {
    max-width: 1100px;
    margin: 0 auto;
}

.lux-title {
    text-align: center;
    font-family: 'Playfair Display', serif;
    font-size: 42px;
    margin-bottom: 40px;
    font-weight: 400;
    letter-spacing: 1px;
}

.checkout-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.checkout-box {
    background: #fafafa;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}

.box-title {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 400;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 15px;
}

.form-group label {
    font-weight: 400;
    font-size: 14px;
    margin-bottom: 5px;
}

input[type="text"], textarea, select {
    padding: 12px;
    border: 1px solid rgba(0,0,0,0.15);
    border-radius: 6px;
    font-family: 'Montserrat', sans-serif;
    font-size: 14px;
    outline: none;
    transition: border 0.3s ease;
}

input[type="text"]:focus, textarea:focus, select:focus {
    border-color: #000;
}

.summary-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    padding-bottom: 1rem;
    margin-bottom: 1rem;
}

.summary-img img {
    width: 80px;
    height: 80px;
    border-radius: 10px;
    object-fit: cover;
}

.summary-info h3 {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    margin: 0 0 5px 0;
}

.summary-info p {
    font-size: 14px;
    margin: 2px 0;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    border-top: 1px solid rgba(0,0,0,0.1);
    margin-top: 1.5rem;
    padding-top: 1rem;
}

.checkout-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 2.5rem;
}

/* Buttons (match cart.php) */
.btn {
    display: inline-block;
    padding: 14px 40px;
    text-decoration: none;
    font-family: 'Montserrat', sans-serif;
    font-size: 12px;
    letter-spacing: 2px;
    text-transform: uppercase;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 400;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    border: 1px solid rgba(0,0,0,0.3);
    background: transparent;
    color: #0a0a0a;
    min-width: 160px;
    text-align: center;
}

.btn span { position: relative; z-index: 2; }

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: #0a0a0a;
    transition: left 0.4s;
    z-index: 1;
}

.btn:hover::before { left: 0; }
.btn:hover { color: #fff; border-color: #0a0a0a; }

.btn-outline {
    background: transparent;
}

@media (max-width: 900px) {
    .checkout-grid {
        grid-template-columns: 1fr;
    }

    .checkout-buttons {
        flex-direction: column;
        gap: 1rem;
        align-items: center;
    }

    .btn {
        width: 100%;
    }
}
</style>
