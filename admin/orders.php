<?php
// admin/orders.php
session_start();
require_once '../includes/config.php';
require_once '../includes/adminheader.php';

// ensure admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// messages
$success_message = '';
$error_message = '';

// Handle status update
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = trim($_POST['new_status']);

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Get the current order status
        $getOrder = $pdo->prepare("SELECT order_status FROM orders WHERE order_id = ?");
        $getOrder->execute([$order_id]);
        $orderData = $getOrder->fetch(PDO::FETCH_ASSOC);
        $old_status = $orderData['order_status'];

        // If changing TO "Cancelled" status, restore product quantities
        if ($new_status === 'Cancelled' && $old_status !== 'Cancelled') {
            // Get all order items
            $getItems = $pdo->prepare("
                SELECT product_id, quantity 
                FROM order_details 
                WHERE order_id = ?
            ");
            $getItems->execute([$order_id]);
            $items = $getItems->fetchAll(PDO::FETCH_ASSOC);

            // Restore quantity for each product
            foreach ($items as $item) {
                $updateStock = $pdo->prepare("
                    UPDATE products 
                    SET stock_quantity = stock_quantity + ? 
                    WHERE product_id = ?
                ");
                $updateStock->execute([$item['quantity'], $item['product_id']]);
            }
        }

        // If changing FROM "Cancelled" to another status, deduct quantities again
        if ($old_status === 'Cancelled' && $new_status !== 'Cancelled') {
            // Get all order items
            $getItems = $pdo->prepare("
                SELECT product_id, quantity 
                FROM order_details 
                WHERE order_id = ?
            ");
            $getItems->execute([$order_id]);
            $items = $getItems->fetchAll(PDO::FETCH_ASSOC);

            // Deduct quantity for each product
            foreach ($items as $item) {
                $updateStock = $pdo->prepare("
                    UPDATE products 
                    SET stock_quantity = stock_quantity - ? 
                    WHERE product_id = ?
                ");
                $updateStock->execute([$item['quantity'], $item['product_id']]);
            }
        }

        // Update order status in orders table
        $update = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $update->execute([$new_status, $order_id]);

        // Fetch customer_id for notification
        $getCust = $pdo->prepare("SELECT customer_id FROM orders WHERE order_id = ?");
        $getCust->execute([$order_id]);
        $row = $getCust->fetch(PDO::FETCH_ASSOC);
        $customer_id = $row ? intval($row['customer_id']) : null;

        // Insert notification (include customer_id to satisfy FK)
        if ($customer_id) {
            $notif = $pdo->prepare("
                INSERT INTO notifications (order_id, customer_id, message, date_created)
                VALUES (?, ?, ?, NOW())
            ");
            $msg = "Order #{$order_id} status updated to '{$new_status}'.";
            $notif->execute([$order_id, $customer_id, $msg]);
        }

        // Commit transaction
        $pdo->commit();

        $success_message = "Order #{$order_id} status updated to '{$new_status}'.";
    } catch (Exception $e) {
        // Rollback on error
        $pdo->rollBack();
        $error_message = "Unable to update order #{$order_id}. Error: " . $e->getMessage();
    }
}

// Get filter and sort parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build SQL query with filters
$query = "
    SELECT 
        o.order_id,
        o.customer_id,
        o.order_date,
        o.order_status,
        o.payment_method,
        c.full_name,
        c.email,
        c.contact_number,
        (SELECT IFNULL(SUM(od.quantity * od.unit_price),0) FROM order_details od WHERE od.order_id = o.order_id) AS total_amount
    FROM orders o
    JOIN customers c ON o.customer_id = c.customer_id
    WHERE 1=1
";

// Add status filter
if ($filter_status !== 'all') {
    $query .= " AND o.order_status = " . $pdo->quote($filter_status);
}

// Add search filter
if (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    $query .= " AND (c.full_name LIKE " . $pdo->quote($search_param) . 
              " OR c.email LIKE " . $pdo->quote($search_param) . 
              " OR o.order_id LIKE " . $pdo->quote($search_param) . ")";
}

// Add sorting
switch ($sort_by) {
    case 'date_asc':
        $query .= " ORDER BY o.order_date ASC";
        break;
    case 'date_desc':
        $query .= " ORDER BY o.order_date DESC";
        break;
    case 'amount_asc':
        $query .= " ORDER BY total_amount ASC";
        break;
    case 'amount_desc':
        $query .= " ORDER BY total_amount DESC";
        break;
    case 'customer':
        $query .= " ORDER BY c.full_name ASC";
        break;
    default:
        $query .= " ORDER BY o.order_date DESC";
}

$orders = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Get statistics - FIXED: Only count revenue from "Received" orders
$stats_query = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN order_status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN order_status = 'On the Way' THEN 1 ELSE 0 END) as on_the_way,
        SUM(CASE WHEN order_status = 'Received' THEN 1 ELSE 0 END) as received,
        SUM(CASE WHEN order_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
        (SELECT IFNULL(SUM(od.quantity * od.unit_price), 0) 
         FROM order_details od 
         JOIN orders o2 ON od.order_id = o2.order_id
         WHERE o2.order_status = 'Received') as total_revenue
    FROM orders
";
$stats = $pdo->query($stats_query)->fetch(PDO::FETCH_ASSOC);
?>

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="admin-main">
    <div class="luxury-orders-container">
        <div class="page-header-lux">
            <h1 class="lux-admin-title">Order Management</h1>
            <p class="lux-admin-subtitle">Oversee and Update Customer Orders</p>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert-lux alert-success">
                <span class="alert-icon">✓</span>
                <?= htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert-lux alert-error">
                <span class="alert-icon">✕</span>
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($stats['total_orders']); ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($stats['pending']); ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-truck"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($stats['on_the_way']); ?></div>
                    <div class="stat-label">On the Way</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($stats['received']); ?></div>
                    <div class="stat-label">Received</div>
                </div>
            </div>
            <div class="stat-card stat-revenue">
                <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
                <div class="stat-content">
                    <div class="stat-value">₱<?= number_format($stats['total_revenue'], 2); ?></div>
                    <div class="stat-label">Total Revenue (Received)</div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="filters-section">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" 
                       id="searchInput" 
                       placeholder="Search by customer, email, or order ID..." 
                       value="<?= htmlspecialchars($search_query); ?>">
            </div>

            <div class="filter-controls">
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select id="statusFilter" class="filter-select">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : ''; ?>>All Orders</option>
                        <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="On the Way" <?= $filter_status === 'On the Way' ? 'selected' : ''; ?>>On the Way</option>
                        <option value="Received" <?= $filter_status === 'Received' ? 'selected' : ''; ?>>Received</option>
                        <option value="Cancelled" <?= $filter_status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Sort By</label>
                    <select id="sortFilter" class="filter-select">
                        <option value="date_desc" <?= $sort_by === 'date_desc' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="date_asc" <?= $sort_by === 'date_asc' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="amount_desc" <?= $sort_by === 'amount_desc' ? 'selected' : ''; ?>>Highest Amount</option>
                        <option value="amount_asc" <?= $sort_by === 'amount_asc' ? 'selected' : ''; ?>>Lowest Amount</option>
                        <option value="customer" <?= $sort_by === 'customer' ? 'selected' : ''; ?>>Customer Name</option>
                    </select>
                </div>

                <button class="btn-reset" onclick="resetFilters()">
                    <i class="fas fa-redo"></i>
                    <span>Reset</span>
                </button>
            </div>
        </div>

        <div class="results-info">
            <p>Showing <strong><?= count($orders); ?></strong> order<?= count($orders) !== 1 ? 's' : ''; ?></p>
            <?php if (!empty($search_query) || $filter_status !== 'all' || $sort_by !== 'date_desc'): ?>
                <p class="active-filters">Active filters applied</p>
            <?php endif; ?>
        </div>

        <div class="orders-table-wrapper">
            <table class="luxury-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Information</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Order Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$orders): ?>
                        <tr><td colspan="6" class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No orders found matching your criteria.</p>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr class="order-row">
                            <td class="order-id">
                                <span class="id-label">#<?= $order['order_id']; ?></span>
                            </td>
                            <td class="customer-info">
                                <div class="customer-name"><?= htmlspecialchars($order['full_name']); ?></div>
                                <div class="customer-email"><?= htmlspecialchars($order['email']); ?></div>
                                <div class="customer-phone"><?= htmlspecialchars($order['contact_number']); ?></div>
                            </td>
                            <td class="order-total">
                                <span class="amount">₱<?= number_format($order['total_amount'], 2); ?></span>
                            </td>
                            <td class="order-status-cell">
                                <?php
                                    $cssClass = strtolower(str_replace(' ', '-', $order['order_status']));
                                ?>
                                <span class="status-badge-lux <?= $cssClass; ?>">
                                    <?= htmlspecialchars($order['order_status']); ?>
                                </span>
                            </td>
                            <td class="order-date">
                                <div class="date-full"><?= date("M d, Y", strtotime($order['order_date'])); ?></div>
                                <div class="date-time"><?= date("g:i A", strtotime($order['order_date'])); ?></div>
                            </td>
                            <td class="actions-cell">
                                <button class="btn-lux btn-view" onclick="viewOrder(<?= $order['order_id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                    <span>View</span>
                                </button>

                                <form method="POST" class="status-update-form">
                                    <input type="hidden" name="order_id" value="<?= $order['order_id']; ?>">
                                    <select name="new_status" class="status-select-lux" aria-label="Change order status">
                                        <option value="Pending" <?= $order['order_status']=='Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="On the Way" <?= $order['order_status']=='On the Way' ? 'selected' : ''; ?>>On the Way</option>
                                        <option value="Received" <?= $order['order_status']=='Received' ? 'selected' : ''; ?>>Received</option>
                                        <option value="Cancelled" <?= $order['order_status']=='Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn-lux btn-update">
                                        <span>Update</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="orderModal" class="modal-lux" aria-hidden="true">
    <div class="modal-overlay" onclick="closeModal()"></div>
    <div class="modal-content-lux">
        <button class="close-modal-lux" onclick="closeModal()" aria-label="Close">
            <span>×</span>
        </button>
        <div id="orderDetails" class="order-details-content"></div>
    </div>
</div>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.admin-main {
    font-family: 'Montserrat', sans-serif;
    background: linear-gradient(to bottom, #fafafa 0%, #ffffff 100%);
    min-height: 100vh;
    padding: 60px 40px;
}

.luxury-orders-container {
    max-width: 1400px;
    margin: 0 auto;
}

/* ===== HEADER ===== */
.page-header-lux {
    text-align: center;
    margin-bottom: 60px;
    padding-bottom: 40px;
    border-bottom: 1px solid rgba(0,0,0,0.08);
}

.lux-admin-title {
    font-family: 'Playfair Display', serif;
    font-size: 48px;
    font-weight: 400;
    letter-spacing: 0.5px;
    margin-bottom: 16px;
    color: #0a0a0a;
}

.lux-admin-subtitle {
    font-size: 11px;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: rgba(0,0,0,0.5);
    font-weight: 400;
}

/* ===== ALERTS ===== */
.alert-lux {
    padding: 20px 30px;
    margin-bottom: 40px;
    border: 1px solid;
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 13px;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
}

.alert-lux .alert-icon {
    font-size: 18px;
    font-weight: 600;
}

.alert-success {
    background: #f0f8f4;
    border-color: rgba(21,87,36,0.2);
    color: #155724;
}

.alert-error {
    background: #fef5f5;
    border-color: rgba(176,42,55,0.2);
    color: #b02a37;
}

.alert-lux:hover {
    transform: translateX(5px);
}

/* ===== STATISTICS GRID ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 25px;
    margin-bottom: 60px;
}

.stat-card {
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.08);
    padding: 30px;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: #0a0a0a;
    transition: width 0.4s ease;
}

.stat-card:hover::before {
    width: 100%;
}

.stat-card:hover {
    transform: translateY(-3px);
    border-color: rgba(0,0,0,0.15);
    box-shadow: 0 15px 45px rgba(0,0,0,0.08);
}

.stat-revenue {
    grid-column: span 1;
}

.stat-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fafafa;
    border: 1px solid rgba(0,0,0,0.08);
    font-size: 20px;
    color: #0a0a0a;
    transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
    background: #0a0a0a;
    color: #ffffff;
    transform: scale(1.1);
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 28px;
    font-weight: 600;
    color: #0a0a0a;
    margin-bottom: 5px;
    letter-spacing: 0.5px;
}

.stat-label {
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: rgba(0,0,0,0.5);
    font-weight: 500;
}

/* ===== FILTERS SECTION ===== */
.filters-section {
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.08);
    padding: 35px;
    margin-bottom: 30px;
    display: flex;
    gap: 25px;
    align-items: flex-end;
    flex-wrap: wrap;
    transition: all 0.3s ease;
}

.filters-section:hover {
    border-color: rgba(0,0,0,0.12);
}

.search-box {
    flex: 1;
    min-width: 280px;
    position: relative;
}

.search-icon {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(0,0,0,0.4);
    font-size: 14px;
}

#searchInput {
    width: 100%;
    padding: 15px 20px 15px 48px;
    border: 1px solid rgba(0,0,0,0.15);
    background: #fafafa;
    font-size: 13px;
    letter-spacing: 0.3px;
    font-family: 'Montserrat', sans-serif;
    transition: all 0.3s ease;
}

#searchInput:focus {
    outline: none;
    border-color: #0a0a0a;
    background: #ffffff;
}

#searchInput::placeholder {
    color: rgba(0,0,0,0.4);
}

.filter-controls {
    display: flex;
    gap: 20px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-label {
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #0a0a0a;
    font-weight: 600;
}

.filter-select {
    padding: 15px 20px;
    border: 1px solid rgba(0,0,0,0.15);
    background: #fafafa;
    font-size: 12px;
    letter-spacing: 0.5px;
    color: #0a0a0a;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Montserrat', sans-serif;
    min-width: 180px;
}

.filter-select:hover,
.filter-select:focus {
    border-color: #0a0a0a;
    outline: none;
    background: #ffffff;
}

.btn-reset {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 15px 30px;
    border: 1px solid rgba(0,0,0,0.2);
    background: transparent;
    color: #0a0a0a;
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    font-family: 'Montserrat', sans-serif;
}

.btn-reset::before {
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

.btn-reset:hover::before {
    width: 100%;
}

.btn-reset:hover {
    color: #ffffff;
    border-color: #0a0a0a;
}

.btn-reset i,
.btn-reset span {
    position: relative;
    z-index: 1;
}

/* ===== RESULTS INFO ===== */
.results-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
    font-size: 12px;
    letter-spacing: 0.3px;
    color: rgba(0,0,0,0.6);
    margin-bottom: 15px;
}

.results-info strong {
    color: #0a0a0a;
    font-weight: 600;
}

.active-filters {
    font-size: 11px;
    color: #0a0a0a;
    font-weight: 500;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

/* ===== TABLE WRAPPER ===== */
.orders-table-wrapper {
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.orders-table-wrapper:hover {
    border-color: rgba(0,0,0,0.12);
    box-shadow: 0 20px 60px rgba(0,0,0,0.06);
}

/* ===== LUXURY TABLE ===== */
.luxury-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.luxury-table thead {
    background: #fafafa;
    border-bottom: 2px solid rgba(0,0,0,0.1);
}

.luxury-table thead th {
    padding: 25px 20px;
    text-align: left;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: #0a0a0a;
    border-right: 1px solid rgba(0,0,0,0.05);
}

.luxury-table thead th:last-child {
    border-right: none;
}

.luxury-table tbody tr {
    border-bottom: 1px solid rgba(0,0,0,0.05);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.luxury-table tbody tr::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 0;
    height: 1px;
    background: #0a0a0a;
    transition: width 0.4s ease;
}

.luxury-table tbody tr:hover::after {
    width: 100%;
}

.luxury-table tbody tr:hover {
    background: #fafafa;
    transform: translateX(3px);
}

.luxury-table tbody td {
    padding: 25px 20px;
    vertical-align: middle;
    color: #2a2a2a;
    letter-spacing: 0.3px;
}

/* ===== TABLE CELLS ===== */
.order-id .id-label {
    font-family: 'Playfair Display', serif;
    font-size: 16px;
    font-weight: 500;
    color: #0a0a0a;
}

.customer-info .customer-name {
    font-weight: 500;
    color: #0a0a0a;
    margin-bottom: 6px;
    font-size: 14px;
}

.customer-info .customer-email {
    font-size: 11px;
    color: rgba(0,0,0,0.5);
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.customer-info .customer-phone {
    font-size: 11px;
    color: rgba(0,0,0,0.4);
    letter-spacing: 0.5px;
}

.order-total .amount {
    font-weight: 600;
    font-size: 15px;
    letter-spacing: 0.5px;
    color: #0a0a0a;
}

.order-date .date-full {
    font-size: 12px;
    letter-spacing: 0.5px;
    color: #0a0a0a;
    margin-bottom: 4px;
}

.order-date .date-time {
    font-size: 11px;
    color: rgba(0,0,0,0.5);
    letter-spacing: 0.5px;
}

.empty-state {
    text-align: center;
    padding: 80px 20px !important;
    color: rgba(0,0,0,0.4);
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 20px;
    display: block;
    color: rgba(0,0,0,0.2);
}

.empty-state p {
    font-size: 13px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

/* ===== STATUS BADGES ===== */
.status-badge-lux {
    display: inline-block;
    padding: 8px 20px;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #ffffff;
    transition: all 0.3s ease;
}

.status-badge-lux:hover {
    transform: scale(1.05);
    letter-spacing: 2.5px;
}

.status-badge-lux.pending { background: #8b8b8b; }
.status-badge-lux.on-the-way { background: #4a4a4a; }
.status-badge-lux.received { background: #0a0a0a; }
.status-badge-lux.cancelled { background: #b02a37; }

/* ===== ACTIONS CELL ===== */
.actions-cell {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.status-update-form {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* ===== BUTTONS ===== */
.btn-lux {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 24px;
    border: 1px solid rgba(0,0,0,0.2);
    background: transparent;
    color: #0a0a0a;
    font-size: 9px;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.btn-lux::before {
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

.btn-lux:hover::before {
    width: 100%;
}

.btn-lux:hover {
    color: #ffffff;
    border-color: #0a0a0a;
}

.btn-lux i,
.btn-lux span {
    position: relative;
    z-index: 1;
}

.btn-view i {
    font-size: 11px;
}

/* ===== STATUS SELECT ===== */
.status-select-lux {
    padding: 10px 16px;
    border: 1px solid rgba(0,0,0,0.15);
    background: #ffffff;
    font-size: 11px;
    letter-spacing: 0.5px;
    color: #0a0a0a;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Montserrat', sans-serif;
}

.status-select-lux:hover,
.status-select-lux:focus {
    border-color: #0a0a0a;
    outline: none;
    background: #fafafa;
}

/* ===== MODAL ===== */
.modal-lux {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    align-items: center;
    justify-content: center;
    animation: modalFadeIn 0.3s ease;
}

@keyframes modalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
}

.modal-content-lux {
    position: relative;
    background: #ffffff;
    padding: 60px;
    width: 800px;
    max-width: 95%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 30px 90px rgba(0,0,0,0.3);
    animation: modalSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 2001;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.close-modal-lux {
    position: absolute;
    right: 30px;
    top: 30px;
    width: 40px;
    height: 40px;
    background: transparent;
    border: 1px solid rgba(0,0,0,0.15);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2002;
}

.close-modal-lux span {
    font-size: 28px;
    line-height: 1;
    color: #0a0a0a;
    transition: all 0.3s ease;
}

.close-modal-lux:hover {
    background: #0a0a0a;
    border-color: #0a0a0a;
    transform: rotate(90deg);
}

.close-modal-lux:hover span {
    color: #ffffff;
}

.order-details-content {
    min-height: 200px;
    font-family: 'Montserrat', sans-serif;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .luxury-table {
        font-size: 12px;
    }
    
    .luxury-table thead th,
    .luxury-table tbody td {
        padding: 20px 15px;
    }

    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
}

@media (max-width: 968px) {
    .admin-main {
        padding: 40px 20px;
    }

    .lux-admin-title {
        font-size: 36px;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .filters-section {
        flex-direction: column;
        align-items: stretch;
    }

    .search-box {
        min-width: 100%;
    }

    .filter-controls {
        flex-direction: column;
        width: 100%;
        gap: 15px;
    }

    .filter-group {
        width: 100%;
    }

    .filter-select {
        width: 100%;
    }

    .btn-reset {
        width: 100%;
        justify-content: center;
    }

    .orders-table-wrapper {
        overflow-x: auto;
    }

    .luxury-table {
        min-width: 900px;
    }

    .modal-content-lux {
        padding: 40px 30px;
        width: 95%;
    }
}

@media (max-width: 768px) {
    .page-header-lux {
        margin-bottom: 50px;
        padding-bottom: 30px;
    }

    .lux-admin-title {
        font-size: 28px;
    }

    .lux-admin-subtitle {
        font-size: 10px;
        letter-spacing: 2px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .actions-cell {
        flex-direction: column;
        align-items: flex-start;
    }

    .status-update-form {
        width: 100%;
        flex-direction: column;
        align-items: stretch;
    }

    .status-select-lux,
    .btn-update {
        width: 100%;
    }

    .modal-content-lux {
        padding: 30px 20px;
    }

    .close-modal-lux {
        right: 15px;
        top: 15px;
    }
}
</style>

<script>
// Search functionality with debounce
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

// Status filter change
document.getElementById('statusFilter').addEventListener('change', function() {
    applyFilters();
});

// Sort filter change
document.getElementById('sortFilter').addEventListener('change', function() {
    applyFilters();
});

// Apply filters function
function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const sort = document.getElementById('sortFilter').value;
    
    let url = 'orders.php?';
    let params = [];
    
    if (search) params.push('search=' + encodeURIComponent(search));
    if (status) params.push('status=' + encodeURIComponent(status));
    if (sort) params.push('sort=' + encodeURIComponent(sort));
    
    window.location.href = url + params.join('&');
}

// Reset filters
function resetFilters() {
    window.location.href = 'orders.php';
}

// View order modal
function viewOrder(orderId) {
    fetch('view_order.php?order_id=' + encodeURIComponent(orderId))
        .then(resp => {
            if (!resp.ok) throw new Error('Network error');
            return resp.text();
        })
        .then(html => {
            document.getElementById('orderDetails').innerHTML = html;
            const modal = document.getElementById('orderModal');
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        })
        .catch(err => {
            alert('Could not load order details.');
            console.error(err);
        });
}

// Close modal
function closeModal() {
    const modal = document.getElementById('orderModal');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = 'auto';
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Auto-dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-lux');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(-20px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>