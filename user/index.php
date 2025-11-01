<?php
ob_start();
session_start();
require_once('../includes/config.php');
require_once('../includes/header.php');

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = (int) $_SESSION['customer_id'];

// ✅ Handle cancel order request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $order_id = (int) $_POST['cancel_order_id'];

    // Update order status only if it belongs to the user and still pending
    $stmt = $conn->prepare("UPDATE orders SET order_status = 'Cancelled' WHERE order_id = ? AND customer_id = ? AND order_status = 'Pending'");
    $stmt->bind_param("ii", $order_id, $customer_id);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit;
}

// Fetch customer info
$stmt = $conn->prepare("SELECT full_name, email, contact_number, address FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

// Fetch user orders
$order_query = "
    SELECT o.order_id, o.order_date, o.order_status, o.delivery_address, o.payment_method,
           SUM(od.quantity * od.unit_price) AS total_amount
    FROM orders o
    LEFT JOIN order_details od ON o.order_id = od.order_id
    WHERE o.customer_id = ?
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500&family=Playfair+Display:wght@400;500&display=swap" rel="stylesheet">

<main class="profile-dashboard">
  <div class="profile-container">
    <h1 class="lux-title">Your Profile</h1>
    <p class="lux-subtitle">Refined details, tailored for you</p>

    <div class="profile-card">
      <div class="profile-detail">
        <p><strong>Name:</strong> <?php echo htmlspecialchars($customer['full_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
        <p><strong>Contact:</strong> <?php echo htmlspecialchars($customer['contact_number']); ?></p>
        <p><strong>Address:</strong> <?php echo htmlspecialchars($customer['address']); ?></p>
      </div>
    </div>

    <div class="profile-actions">
      <a href="edit_profile.php" class="btn"><span>Edit Info</span></a>
      <a href="../user/logout.php" class="btn"><span>Logout</span></a>
      <form action="delete_account.php" method="POST" style="display:inline;">
        <button type="submit" name="delete_account" class="btn btn-danger"
          onclick="return confirm('Delete your account permanently?');">
          <span>Delete</span>
        </button>
      </form>
    </div>

    <!-- ========================= -->
    <!-- My Orders Section -->
    <!-- ========================= -->
    <section class="orders-section">
      <h2 class="lux-title" style="margin-top:80px;">My Orders</h2>
      <p class="lux-subtitle">Your recent purchases</p>

      <?php if ($orders->num_rows > 0): ?>
        <?php while ($order = $orders->fetch_assoc()): ?>
          <div class="order-card">
            <div class="order-header">
              <div>
                <h3>Order #<?php echo $order['order_id']; ?></h3>
                <p class="order-date"><?php echo date("F j, Y • g:i A", strtotime($order['order_date'])); ?></p>
              </div>
              <span class="order-status <?php echo strtolower(str_replace(' ', '-', $order['order_status'])); ?>">
                <?php echo htmlspecialchars($order['order_status']); ?>
              </span>
            </div>

            <div class="order-details">
              <p><strong>Delivery:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
              <p><strong>Payment:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>

              <?php
              $detail_stmt = $conn->prepare("
                  SELECT p.product_name, od.quantity, od.unit_price
                  FROM order_details od
                  JOIN products p ON od.product_id = p.product_id
                  WHERE od.order_id = ?
              ");
              $detail_stmt->bind_param("i", $order['order_id']);
              $detail_stmt->execute();
              $items = $detail_stmt->get_result();
              ?>

              <div class="order-items">
                <?php while ($item = $items->fetch_assoc()): ?>
                  <div class="order-item">
                    <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                    <span class="item-qty">x<?php echo $item['quantity']; ?></span>
                    <span class="item-price">₱<?php echo number_format($item['unit_price'], 2); ?></span>
                  </div>
                <?php endwhile; ?>
              </div>
              <?php $detail_stmt->close(); ?>

              <div class="order-total">
                <p><strong>Total:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?></p>
              </div>

              <!-- ✅ Cancel Order Button -->
              <?php if (strtolower($order['order_status']) === 'pending'): ?>
                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');" class="cancel-form">
                  <input type="hidden" name="cancel_order_id" value="<?php echo $order['order_id']; ?>">
                  <button type="submit" class="btn-cancel">Cancel Order</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="no-orders">You haven’t placed any orders yet.</p>
      <?php endif; ?>
    </section>
  </div>
</main>

<?php require_once('../includes/footer.php'); ?>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Montserrat', sans-serif;
  background: #ffffff;
  color: #1a1a1a;
  line-height: 1.6;
}

.profile-dashboard {
  min-height: 100vh;
  padding: 120px 40px 80px;
  background: linear-gradient(to bottom, #fafafa 0%, #ffffff 100%);
}

.profile-container {
  max-width: 1100px;
  margin: 0 auto;
}

/* ===== LUXURY TITLES ===== */
.lux-title {
  font-family: 'Playfair Display', serif;
  font-size: 48px;
  font-weight: 400;
  letter-spacing: 0.5px;
  margin-bottom: 16px;
  color: #0a0a0a;
  text-align: center;
}

.lux-subtitle {
  font-family: 'Montserrat', sans-serif;
  font-size: 11px;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: rgba(0,0,0,0.5);
  margin-bottom: 60px;
  text-align: center;
  font-weight: 400;
}

/* ===== PROFILE CARD ===== */
.profile-card {
  background: #ffffff;
  border: 1px solid rgba(0,0,0,0.08);
  padding: 60px;
  margin: 0 auto 50px;
  max-width: 800px;
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.profile-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(0,0,0,0.1), transparent);
  transform: translateX(-100%);
  transition: transform 0.8s ease;
}

.profile-card:hover::before {
  transform: translateX(100%);
}

.profile-card:hover {
  border-color: rgba(0,0,0,0.15);
  box-shadow: 0 20px 60px rgba(0,0,0,0.08);
  transform: translateY(-2px);
}

.profile-detail {
  text-align: left;
}

.profile-detail p {
  font-size: 15px;
  line-height: 2.2;
  color: #2a2a2a;
  letter-spacing: 0.3px;
  border-bottom: 1px solid rgba(0,0,0,0.05);
  padding: 18px 0;
  transition: all 0.3s ease;
}

.profile-detail p:last-child {
  border-bottom: none;
}

.profile-detail p:hover {
  padding-left: 12px;
  color: #0a0a0a;
}

.profile-detail strong {
  display: inline-block;
  min-width: 120px;
  font-weight: 500;
  color: #0a0a0a;
  font-size: 11px;
  letter-spacing: 2px;
  text-transform: uppercase;
}

/* ===== PROFILE ACTIONS ===== */
.profile-actions {
  display: flex;
  gap: 20px;
  justify-content: center;
  align-items: center;
  margin: 60px 0 100px;
  flex-wrap: wrap;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 16px 48px;
  border: 1px solid rgba(0,0,0,0.2);
  background: transparent;
  color: #0a0a0a;
  text-transform: uppercase;
  font-size: 10px;
  letter-spacing: 2.5px;
  font-weight: 500;
  position: relative;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  text-decoration: none;
  cursor: pointer;
  min-width: 180px;
}

.btn span {
  position: relative;
  z-index: 2;
  transition: color 0.4s ease;
}

.btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 0;
  height: 100%;
  background: #0a0a0a;
  transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 1;
}

.btn:hover::before {
  width: 100%;
}

.btn:hover {
  border-color: #0a0a0a;
}

.btn:hover span {
  color: #ffffff;
}

.btn-danger {
  border-color: rgba(176,42,55,0.3);
  color: #b02a37;
}

.btn-danger::before {
  background: #b02a37;
}

.btn-danger:hover {
  border-color: #b02a37;
}

/* ===== ORDERS SECTION ===== */
.orders-section {
  margin-top: 120px;
  padding-top: 80px;
  border-top: 1px solid rgba(0,0,0,0.08);
}

.orders-section .lux-title {
  margin-bottom: 12px;
}

.orders-section .lux-subtitle {
  margin-bottom: 80px;
}

.order-card {
  background: #ffffff;
  border: 1px solid rgba(0,0,0,0.08);
  padding: 50px;
  margin-bottom: 40px;
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.order-card::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 1px;
  background: #0a0a0a;
  transition: width 0.5s ease;
}

.order-card:hover::after {
  width: 90%;
}

.order-card:hover {
  border-color: rgba(0,0,0,0.15);
  box-shadow: 0 25px 70px rgba(0,0,0,0.08);
  transform: translateY(-3px);
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 40px;
  padding-bottom: 30px;
  border-bottom: 1px solid rgba(0,0,0,0.06);
}

.order-header h3 {
  font-family: 'Playfair Display', serif;
  font-size: 24px;
  font-weight: 400;
  margin-bottom: 8px;
  color: #0a0a0a;
}

.order-date {
  font-size: 11px;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: rgba(0,0,0,0.5);
  font-weight: 400;
}

.order-status {
  padding: 10px 24px;
  font-size: 9px;
  letter-spacing: 2px;
  text-transform: uppercase;
  font-weight: 600;
  color: #ffffff;
  border-radius: 2px;
  transition: all 0.3s ease;
}

.order-status:hover {
  transform: scale(1.05);
  letter-spacing: 2.5px;
}

.order-status.pending { background: #8b8b8b; }
.order-status.cancelled { background: #b02a37; }
.order-status.received { background: #0a0a0a; }
.order-status.on-the-way { background: #4a4a4a; }

.order-details {
  text-align: left;
}

.order-details > p {
  font-size: 14px;
  margin-bottom: 16px;
  color: #2a2a2a;
  letter-spacing: 0.3px;
  line-height: 1.8;
}

.order-details strong {
  font-weight: 500;
  color: #0a0a0a;
  font-size: 11px;
  letter-spacing: 2px;
  text-transform: uppercase;
  margin-right: 12px;
}

.order-items {
  margin: 35px 0;
  padding: 30px;
  background: #fafafa;
  border-left: 2px solid rgba(0,0,0,0.1);
  transition: all 0.3s ease;
}

.order-items:hover {
  border-left-color: #0a0a0a;
  background: #f7f7f7;
}

.order-item {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 20px;
  align-items: center;
  font-size: 13px;
  padding: 14px 0;
  border-bottom: 1px solid rgba(0,0,0,0.05);
  transition: all 0.3s ease;
}

.order-item:last-child {
  border-bottom: none;
}

.order-item:hover {
  padding-left: 10px;
  color: #0a0a0a;
}

.item-name {
  font-weight: 400;
  letter-spacing: 0.3px;
  color: #2a2a2a;
}

.item-qty {
  font-size: 11px;
  color: rgba(0,0,0,0.6);
  letter-spacing: 1px;
  text-align: center;
}

.item-price {
  font-weight: 500;
  letter-spacing: 0.5px;
  text-align: right;
  color: #0a0a0a;
}

.order-total {
  margin-top: 30px;
  padding-top: 25px;
  border-top: 2px solid rgba(0,0,0,0.1);
  text-align: right;
}

.order-total p {
  font-size: 16px;
  letter-spacing: 0.5px;
}

.order-total strong {
  font-weight: 600;
  font-size: 11px;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  margin-right: 16px;
}

/* ===== CANCEL BUTTON ===== */
.cancel-form {
  margin-top: 30px;
  padding-top: 20px;
  text-align: right;
  border-top: 1px solid rgba(0,0,0,0.05);
}

.btn-cancel {
  background: transparent;
  border: 1px solid rgba(0,0,0,0.2);
  color: #0a0a0a;
  font-size: 10px;
  padding: 12px 36px;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.btn-cancel::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 0;
  height: 100%;
  background: #0a0a0a;
  transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 0;
}

.btn-cancel:hover::before {
  width: 100%;
}

.btn-cancel:hover {
  color: #ffffff;
  border-color: #0a0a0a;
}

.no-orders {
  text-align: center;
  color: rgba(0,0,0,0.4);
  margin-top: 80px;
  font-size: 13px;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  font-weight: 400;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .profile-dashboard {
    padding: 80px 20px 60px;
  }

  .lux-title {
    font-size: 36px;
  }

  .lux-subtitle {
    font-size: 10px;
    letter-spacing: 2px;
    margin-bottom: 40px;
  }

  .profile-card {
    padding: 40px 30px;
  }

  .profile-detail strong {
    display: block;
    margin-bottom: 6px;
  }

  .profile-actions {
    flex-direction: column;
    gap: 15px;
    margin: 40px 0 80px;
  }

  .btn {
    width: 100%;
    min-width: auto;
  }

  .orders-section {
    margin-top: 80px;
    padding-top: 60px;
  }

  .order-card {
    padding: 30px 25px;
  }

  .order-header {
    flex-direction: column;
    gap: 16px;
    margin-bottom: 30px;
    padding-bottom: 25px;
  }

  .order-header h3 {
    font-size: 20px;
  }

  .order-status {
    align-self: flex-start;
  }

  .order-items {
    padding: 20px;
    margin: 25px 0;
  }

  .order-item {
    grid-template-columns: 1fr;
    gap: 8px;
    padding: 16px 0;
  }

  .item-qty,
  .item-price {
    text-align: left;
  }

  .cancel-form {
    text-align: center;
  }

  .btn-cancel {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .lux-title {
    font-size: 28px;
  }

  .profile-card {
    padding: 30px 20px;
  }

  .order-card {
    padding: 25px 20px;
  }
}
</style>

<?php ob_end_flush(); ?>
