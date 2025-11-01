<?php
// admin/view_order.php
require_once '../includes/config.php';

if (!isset($_GET['order_id'])) {
    http_response_code(400);
    exit('Invalid request');
}

$order_id = intval($_GET['order_id']);

// Fetch order and customer details
$stmt = $pdo->prepare("
    SELECT o.*, c.full_name, c.email, c.contact_number, c.address AS customer_address
    FROM orders o
    JOIN customers c ON o.customer_id = c.customer_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    exit('Order not found.');
}

// Fetch order items
$itemsStmt = $pdo->prepare("
    SELECT od.*, p.product_name, p.image_path
    FROM order_details od
    LEFT JOIN products p ON od.product_id = p.product_id
    WHERE od.order_id = ?
");
$itemsStmt->execute([$order_id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// compute totals (also safe if DB already stores)
$total = 0;
foreach ($items as $it) {
    $subtotal = ($it['quantity'] * $it['unit_price']);
    $total += $subtotal;
}
?>

<div class="order-modal-inner">
    <h2>Order #<?= htmlspecialchars($order['order_id']); ?></h2>
    <p><strong>Status:</strong> <?= htmlspecialchars($order['order_status']); ?></p>
    <p><strong>Placed:</strong> <?= date("M d, Y H:i", strtotime($order['order_date'])); ?></p>

    <hr>

    <h3>Customer</h3>
    <p><strong><?= htmlspecialchars($order['full_name']); ?></strong></p>
    <p><?= nl2br(htmlspecialchars($order['customer_address'] ?? $order['delivery_address'] ?? '')); ?></p>
    <p><?= htmlspecialchars($order['contact_number']); ?> · <?= htmlspecialchars($order['email']); ?></p>

    <hr>

    <h3>Items</h3>
    <table style="width:100%; border-collapse:collapse; margin-top:10px;">
        <thead>
            <tr style="text-align:left; border-bottom:1px solid #eee;">
                <th style="padding:6px 8px;">Product</th>
                <th style="padding:6px 8px;">Qty</th>
                <th style="padding:6px 8px;">Unit</th>
                <th style="padding:6px 8px; text-align:right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): 
                $subtotal = $it['quantity'] * $it['unit_price'];
            ?>
            <tr>
                <td style="padding:8px 8px;">
                    <?= htmlspecialchars($it['product_name'] ?? 'Product #' . $it['product_id']); ?>
                </td>
                <td style="padding:8px 8px; width:70px;"><?= intval($it['quantity']); ?></td>
                <td style="padding:8px 8px;">₱<?= number_format($it['unit_price'],2); ?></td>
                <td style="padding:8px 8px; text-align:right;">₱<?= number_format($subtotal,2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px;">
        <div>
            <p><strong>Payment:</strong> <?= htmlspecialchars($order['payment_method'] ?? 'COD'); ?></p>
            <?php if (!empty($order['payment_reference'])): ?>
                <p><strong>Reference:</strong> <?= htmlspecialchars($order['payment_reference']); ?></p>
            <?php endif; ?>
            <?php if (!empty($order['date_received'])): ?>
                <p><strong>Date Received:</strong> <?= date("M d, Y H:i", strtotime($order['date_received'])); ?></p>
            <?php endif; ?>
        </div>
        <div style="text-align:right;">
            <h3 style="margin:0;">Total</h3>
            <p style="font-size:1.25rem; font-weight:700; margin:0;">₱<?= number_format($total,2); ?></p>
        </div>
    </div>
</div>

<style>
.order-modal-inner h2 { margin:0 0 8px 0; font-family: 'Playfair Display', serif; }
.order-modal-inner h3 { margin:12px 0 6px 0; font-size:1rem; font-weight:600; }
.order-modal-inner p { margin:6px 0; color:#333; font-size:0.95rem; }
</style>
