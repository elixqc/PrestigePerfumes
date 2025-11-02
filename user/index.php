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

// ✅ Handle cancel order request with stock restoration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $order_id = (int) $_POST['cancel_order_id'];

    try {
        // Start transaction
        $conn->begin_transaction();

        // Check if order belongs to user and is still pending
        $check_stmt = $conn->prepare("SELECT order_status FROM orders WHERE order_id = ? AND customer_id = ?");
        $check_stmt->bind_param("ii", $order_id, $customer_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $order = $result->fetch_assoc();
        $check_stmt->close();

        if ($order && $order['order_status'] === 'Pending') {
            // Get all order items to restore stock
            $items_stmt = $conn->prepare("SELECT product_id, quantity FROM order_details WHERE order_id = ?");
            $items_stmt->bind_param("i", $order_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();

            // Restore stock for each product
            while ($item = $items_result->fetch_assoc()) {
                $update_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
                $update_stock->bind_param("ii", $item['quantity'], $item['product_id']);
                $update_stock->execute();
                $update_stock->close();
            }
            $items_stmt->close();

            // Update order status to Cancelled
            $cancel_stmt = $conn->prepare("UPDATE orders SET order_status = 'Cancelled' WHERE order_id = ?");
            $cancel_stmt->bind_param("i", $order_id);
            $cancel_stmt->execute();
            $cancel_stmt->close();

            // Commit transaction
            $conn->commit();
        }
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
    }

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

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:wght@400;500&display=swap" rel="stylesheet">

<main class="profile-dashboard">
  <div class="profile-container">
    <div class="page-header">
      <h1 class="lux-title">Your Profile</h1>
      <p class="lux-subtitle">Refined details, tailored for you</p>
    </div>

    <div class="profile-card">
      <div class="profile-detail">
        <div class="detail-row">
          <span class="detail-label">Name</span>
          <span class="detail-value"><?php echo htmlspecialchars($customer['full_name']); ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Email</span>
          <span class="detail-value"><?php echo htmlspecialchars($customer['email']); ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Contact</span>
          <span class="detail-value"><?php echo htmlspecialchars($customer['contact_number']); ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Address</span>
          <span class="detail-value"><?php echo htmlspecialchars($customer['address']); ?></span>
        </div>
      </div>
    </div>

    <div class="profile-actions">
      <a href="edit_profile.php" class="btn btn-primary"><span>Edit Profile</span></a>
      <a href="../user/logout.php" class="btn btn-secondary"><span>Logout</span></a>
      <form action="delete_account.php" method="POST" style="display:inline;">
        <button type="submit" name="delete_account" class="btn btn-danger"
          onclick="return confirm('Delete your account permanently? This action cannot be undone.');">
          <span>Delete Account</span>
        </button>
      </form>
    </div>

    <!-- ========================= -->
    <!-- My Orders Section -->
    <!-- ========================= -->
    <section class="orders-section">
      <div class="section-header">
        <h2 class="lux-title">My Orders</h2>
        <p class="lux-subtitle">Your recent purchases</p>
      </div>

      <?php if ($orders->num_rows > 0): ?>
        <div class="orders-grid">
          <?php while ($order = $orders->fetch_assoc()): ?>
            <div class="order-card">
              <div class="order-header">
                <div class="order-info">
                  <h3 class="order-number">Order #<?php echo $order['order_id']; ?></h3>
                  <p class="order-date"><?php echo date("M j, Y • g:i A", strtotime($order['order_date'])); ?></p>
                </div>
                <span class="order-status <?php echo strtolower(str_replace(' ', '-', $order['order_status'])); ?>">
                  <?php echo htmlspecialchars($order['order_status']); ?>
                </span>
              </div>

              <div class="order-body">
                <div class="order-meta">
                  <div class="meta-item">
                    <span class="meta-label">Delivery</span>
                    <span class="meta-value"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                  </div>
                  <div class="meta-item">
                    <span class="meta-label">Payment</span>
                    <span class="meta-value"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                  </div>
                </div>

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
                  <div class="items-header">Order Items</div>
                  <?php while ($item = $items->fetch_assoc()): ?>
                    <div class="order-item">
                      <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                      <span class="item-qty">×<?php echo $item['quantity']; ?></span>
                      <span class="item-price">₱<?php echo number_format($item['unit_price'], 2); ?></span>
                    </div>
                  <?php endwhile; ?>
                </div>
                <?php $detail_stmt->close(); ?>

                <div class="order-footer">
                  <div class="order-total">
                    <span class="total-label">Total Amount</span>
                    <span class="total-value">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                  </div>

                  <?php if (strtolower($order['order_status']) === 'pending'): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order? Items will be returned to stock.');" class="cancel-form">
                      <input type="hidden" name="cancel_order_id" value="<?php echo $order['order_id']; ?>">
                      <button type="submit" class="btn-cancel"><span>Cancel Order</span></button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="no-orders">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
          </svg>
          <p>You haven't placed any orders yet.</p>
          <a href="../items/index.php" class="btn btn-primary" style="margin-top: 20px;"><span>Start Shopping</span></a>
        </div>
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
  padding: 100px 30px 60px;
  background: linear-gradient(to bottom, #fafafa 0%, #ffffff 100%);
}

.profile-container {
  max-width: 1000px;
  margin: 0 auto;
}

/* ===== PAGE HEADER ===== */
.page-header {
  text-align: center;
  margin-bottom: 50px;
  padding-bottom: 30px;
  border-bottom: 1px solid rgba(0,0,0,0.06);
}

.lux-title {
  font-family: 'Playfair Display', serif;
  font-size: 36px;
  font-weight: 400;
  letter-spacing: 0.5px;
  margin-bottom: 12px;
  color: #0a0a0a;
}

.lux-subtitle {
  font-family: 'Montserrat', sans-serif;
  font-size: 10px;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  color: rgba(0,0,0,0.5);
  font-weight: 400;
}

/* ===== PROFILE CARD ===== */
.profile-card {
  background: #ffffff;
  border: 1px solid rgba(0,0,0,0.08);
  padding: 40px;
  margin-bottom: 30px;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.profile-card:hover {
  border-color: rgba(0,0,0,0.12);
  box-shadow: 0 15px 40px rgba(0,0,0,0.06);
}

.profile-detail {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.detail-row {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 20px;
  padding: 18px 0;
  border-bottom: 1px solid rgba(0,0,0,0.05);
  transition: all 0.3s ease;
  align-items: center;
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-row:hover {
  padding-left: 8px;
  background: rgba(0,0,0,0.01);
}

.detail-label {
  font-size: 10px;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: rgba(0,0,0,0.6);
  font-weight: 500;
}

.detail-value {
  font-size: 13px;
  color: #2a2a2a;
  letter-spacing: 0.3px;
}

/* ===== PROFILE ACTIONS ===== */
.profile-actions {
  display: flex;
  gap: 15px;
  justify-content: center;
  align-items: center;
  margin: 40px 0 60px;
  flex-wrap: wrap;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 32px;
  border: 1px solid rgba(0,0,0,0.15);
  background: transparent;
  color: #0a0a0a;
  text-transform: uppercase;
  font-size: 9px;
  letter-spacing: 2px;
  font-weight: 500;
  position: relative;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  text-decoration: none;
  cursor: pointer;
  font-family: 'Montserrat', sans-serif;
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

.btn-secondary {
  border-color: rgba(0,0,0,0.12);
}

.btn-danger {
  border-color: rgba(176,42,55,0.25);
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
  margin-top: 80px;
  padding-top: 50px;
  border-top: 1px solid rgba(0,0,0,0.06);
}

.section-header {
  text-align: center;
  margin-bottom: 50px;
}

.section-header .lux-title {
  font-size: 32px;
  margin-bottom: 10px;
}

.orders-grid {
  display: grid;
  gap: 30px;
}

.order-card {
  background: #ffffff;
  border: 1px solid rgba(0,0,0,0.08);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.order-card:hover {
  border-color: rgba(0,0,0,0.15);
  box-shadow: 0 20px 50px rgba(0,0,0,0.06);
  transform: translateY(-2px);
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 25px 30px;
  background: #fafafa;
  border-bottom: 1px solid rgba(0,0,0,0.06);
}

.order-number {
  font-family: 'Playfair Display', serif;
  font-size: 18px;
  font-weight: 400;
  margin-bottom: 4px;
  color: #0a0a0a;
}

.order-date {
  font-size: 10px;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: rgba(0,0,0,0.5);
  font-weight: 400;
}

.order-status {
  padding: 6px 18px;
  font-size: 8px;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  font-weight: 600;
  color: #ffffff;
  transition: all 0.3s ease;
}

.order-status:hover {
  transform: scale(1.05);
}

.order-status.pending { background: #8b8b8b; }
.order-status.cancelled { background: #b02a37; }
.order-status.received { background: #0a0a0a; }
.order-status.on-the-way { background: #4a4a4a; }

.order-body {
  padding: 30px;
}

.order-meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 25px;
  padding-bottom: 25px;
  border-bottom: 1px solid rgba(0,0,0,0.05);
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.meta-label {
  font-size: 9px;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: rgba(0,0,0,0.5);
  font-weight: 500;
}

.meta-value {
  font-size: 12px;
  color: #2a2a2a;
  letter-spacing: 0.3px;
}

.order-items {
  background: #fafafa;
  border: 1px solid rgba(0,0,0,0.05);
  padding: 20px;
  margin-bottom: 25px;
}

.items-header {
  font-size: 9px;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: rgba(0,0,0,0.6);
  font-weight: 500;
  margin-bottom: 15px;
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.order-item {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 15px;
  align-items: center;
  font-size: 12px;
  padding: 10px 0;
  border-bottom: 1px solid rgba(0,0,0,0.04);
  transition: all 0.3s ease;
}

.order-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.order-item:hover {
  padding-left: 6px;
}

.item-name {
  font-weight: 400;
  letter-spacing: 0.3px;
  color: #2a2a2a;
}

.item-qty {
  font-size: 11px;
  color: rgba(0,0,0,0.5);
  letter-spacing: 0.5px;
  min-width: 30px;
  text-align: center;
}

.item-price {
  font-weight: 500;
  letter-spacing: 0.3px;
  text-align: right;
  color: #0a0a0a;
  min-width: 90px;
}

.order-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 20px;
  border-top: 1px solid rgba(0,0,0,0.08);
  gap: 20px;
  flex-wrap: wrap;
}

.order-total {
  display: flex;
  align-items: center;
  gap: 15px;
}

.total-label {
  font-size: 9px;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: rgba(0,0,0,0.6);
  font-weight: 500;
}

.total-value {
  font-size: 16px;
  font-weight: 600;
  letter-spacing: 0.5px;
  color: #0a0a0a;
}

/* ===== CANCEL BUTTON ===== */
.cancel-form {
  display: inline-block;
}

.btn-cancel {
  background: transparent;
  border: 1px solid rgba(0,0,0,0.15);
  color: #0a0a0a;
  font-size: 8px;
  padding: 10px 24px;
  letter-spacing: 2px;
  text-transform: uppercase;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  font-family: 'Montserrat', sans-serif;
}

.btn-cancel span {
  position: relative;
  z-index: 2;
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
  z-index: 1;
}

.btn-cancel:hover::before {
  width: 100%;
}

.btn-cancel:hover {
  border-color: #0a0a0a;
}

.btn-cancel:hover span {
  color: #ffffff;
}

.no-orders {
  text-align: center;
  padding: 80px 20px;
  color: rgba(0,0,0,0.4);
}

.no-orders svg {
  margin-bottom: 20px;
  opacity: 0.3;
}

.no-orders p {
  font-size: 12px;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  font-weight: 400;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
  .profile-dashboard {
    padding: 80px 20px 50px;
  }

  .page-header {
    margin-bottom: 40px;
    padding-bottom: 25px;
  }

  .lux-title {
    font-size: 28px;
  }

  .lux-subtitle {
    font-size: 9px;
    letter-spacing: 2px;
  }

  .profile-card {
    padding: 30px 25px;
  }

  .detail-row {
    grid-template-columns: 1fr;
    gap: 8px;
    padding: 16px 0;
  }

  .detail-label {
    font-size: 9px;
  }

  .profile-actions {
    flex-direction: column;
    gap: 12px;
    margin: 30px 0 50px;
  }

  .btn {
    width: 100%;
    padding: 14px 0;
  }

  .orders-section {
    margin-top: 60px;
    padding-top: 40px;
  }

  .section-header {
    margin-bottom: 40px;
  }

  .section-header .lux-title {
    font-size: 26px;
  }

  .orders-grid {
    gap: 25px;
  }

  .order-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
    padding: 20px 25px;
  }

  .order-number {
    font-size: 16px;
  }

  .order-body {
    padding: 25px 20px;
  }

  .order-meta {
    grid-template-columns: 1fr;
    gap: 16px;
    margin-bottom: 20px;
    padding-bottom: 20px;
  }

  .order-items {
    padding: 16px;
  }

  .order-item {
    grid-template-columns: 1fr;
    gap: 6px;
  }

  .item-qty,
  .item-price {
    text-align: left;
  }

  .order-footer {
    flex-direction: column;
    align-items: stretch;
    gap: 15px;
  }

  .order-total {
    justify-content: space-between;
  }

  .btn-cancel {
    width: 100%;
  }

  .no-orders {
    padding: 60px 20px;
  }
}

@media (max-width: 480px) {
  .lux-title {
    font-size: 24px;
  }

  .profile-card {
    padding: 25px 20px;
  }

  .order-header {
    padding: 18px 20px;
  }

  .order-body {
    padding: 20px 16px;
  }
}
</style>

<?php ob_end_flush(); ?>