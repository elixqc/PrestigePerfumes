<?php
session_start();
require_once('../includes/config.php');

if (!isset($_SESSION['customer_id'])) {
    header('Location: ../user/login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $contact = trim($_POST['contact_number']);
    $address = trim($_POST['address']);
    $payment = trim($_POST['payment_method']);
    $total = floatval($_POST['total_amount']);

    // 1️⃣ Create order
    $stmt = $conn->prepare("INSERT INTO orders (customer_id, delivery_address, payment_method, order_status) VALUES (?, ?, ?, 'Pending')");
    $stmt->bind_param("iss", $customer_id, $address, $payment);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // 2️⃣ Move cart items into order_details
    $stmt = $conn->prepare("
        INSERT INTO order_details (order_id, product_id, quantity, unit_price)
        SELECT ?, c.product_id, c.quantity, p.price
        FROM cart c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.customer_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $customer_id);
    $stmt->execute();
    $stmt->close();

    // 3️⃣ Update product stock
    $stmt = $conn->prepare("
        UPDATE products p
        JOIN cart c ON p.product_id = c.product_id
        SET p.stock_quantity = p.stock_quantity - c.quantity
        WHERE c.customer_id = ?
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $stmt->close();

    // 4️⃣ Create notification
    $msg = "Order #$order_id has been placed successfully!";
    $stmt = $conn->prepare("INSERT INTO notifications (order_id, customer_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $order_id, $customer_id, $msg);
    $stmt->execute();
    $stmt->close();

    // 5️⃣ Clear cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $stmt->close();

    header("Location: checkout_success.php?order_id=$order_id");
    exit();
}
?>
