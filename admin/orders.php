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

    // Update order status in orders table
    $update = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    if ($update->execute([$new_status, $order_id])) {

        // fetch customer_id for notification
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

        $success_message = "Order #{$order_id} status updated to '{$new_status}'.";
    } else {
        $error_message = "Unable to update order #{$order_id}.";
    }
}

// Fetch orders and compute totals from order_details
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
    ORDER BY o.order_date DESC
";
$orders = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-main">
    <div class="product-management-container">
        <div class="page-header">
            <h2>Manage Orders</h2>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="products-table-container">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$orders): ?>
                        <tr><td colspan="6" style="text-align:center; padding:2rem; color:#6c757d;">No orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= $order['order_id']; ?></td>
                            <td>
                                <strong><?= htmlspecialchars($order['full_name']); ?></strong><br>
                                <small><?= htmlspecialchars($order['email']); ?></small>
                            </td>
                            <td>₱<?= number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <?php
                                    $cssClass = strtolower(str_replace(' ', '-', $order['order_status']));
                                ?>
                                <span class="status-badge <?= $cssClass; ?>"><?= htmlspecialchars($order['order_status']); ?></span>
                            </td>
                            <td><?= date("M d, Y", strtotime($order['order_date'])); ?></td>
                            <td class="actions">
                                <button class="btn-edit" onclick="viewOrder(<?= $order['order_id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>

                                <form method="POST" style="display:inline-block; margin-left:0.5rem;">
                                    <input type="hidden" name="order_id" value="<?= $order['order_id']; ?>">
                                    <select name="new_status" class="status-select" aria-label="Change order status">
                                        <option value="Pending" <?= $order['order_status']=='Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="On the Way" <?= $order['order_status']=='On the Way' ? 'selected' : ''; ?>>On the Way</option>
                                        <option value="Received" <?= $order['order_status']=='Received' ? 'selected' : ''; ?>>Received</option>
                                        <option value="Cancelled" <?= $order['order_status']=='Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn-filter">Update</button>
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
<div id="orderModal" class="modal" aria-hidden="true">
    <div class="modal-content">
        <button class="close-modal" onclick="closeModal()" aria-label="Close">&times;</button>
        <div id="orderDetails" style="min-height:120px;"></div>
    </div>
</div>

<style>
/* Status badges */
.status-badge { padding:6px 12px; border-radius:20px; font-size:0.85rem; font-weight:600; text-transform:capitalize; }
.status-badge.pending { background:#f8d7da; color:#721c24; }
.status-badge.on-the-way { background:#fff3cd; color:#856404; }
.status-badge.received { background:#d4edda; color:#155724; }
.status-badge.cancelled { background:#f8d7da; color:#721c24; }

/* status select */
.status-select { padding:6px; border-radius:6px; border:1px solid #dee2e6; font-size:0.9rem; }

/* Modal */
.modal { display:none; position:fixed; z-index:1100; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; }
.modal-content { background:#fff; padding:2rem; border-radius:10px; width:720px; max-width:95%; box-shadow:0 8px 24px rgba(0,0,0,0.2); animation:fadeIn .22s ease; position:relative; }
@keyframes fadeIn { from { opacity:0; transform: scale(0.98); } to { opacity:1; transform: scale(1); } }
.close-modal { position:absolute; right:18px; top:12px; font-size:26px; background:none; border:none; cursor:pointer; }

/* Improve table hover */
.products-table tbody tr { transition: all 0.25s ease; }
.products-table tbody tr:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.04); background:#f8f9fa; }

/* small tweaks */
.actions .btn-edit { padding:6px 10px; background:#2c3e50; color:#fff; border-radius:6px; border:none; cursor:pointer; }
.btn-filter { padding:8px 12px; background:#c5a253; color:#fff; border-radius:6px; border:none; cursor:pointer; margin-left:6px; }
</style>

<script>
function viewOrder(orderId) {
    fetch('view_order.php?order_id=' + encodeURIComponent(orderId))
        .then(resp => {
            if (!resp.ok) throw new Error('Network error');
            return resp.text();
        })
        .then(html => {
            document.getElementById('orderDetails').innerHTML = html;
            document.getElementById('orderModal').style.display = 'flex';
            document.getElementById('orderModal').setAttribute('aria-hidden','false');
        })
        .catch(err => {
            alert('Could not load order details.');
            console.error(err);
        });
}

function closeModal() {
    document.getElementById('orderModal').style.display = 'none';
    document.getElementById('orderModal').setAttribute('aria-hidden','true');
}
</script>

<?php require_once '../includes/footer.php'; ?>
