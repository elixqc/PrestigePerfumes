<?php
require_once('../includes/config.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SESSION['customer_id'])) {
        $customer_id = $_SESSION['customer_id'];
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        if ($product_id > 0 && $quantity > 0) {
            // Check stock
            $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ? AND is_active = 1");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                if ($quantity <= $row['stock_quantity']) {
                    // Insert or update cart
                    $stmt2 = $conn->prepare("SELECT quantity FROM cart WHERE customer_id = ? AND product_id = ?");
                    $stmt2->bind_param("ii", $customer_id, $product_id);
                    $stmt2->execute();
                    $cartResult = $stmt2->get_result();
                    if ($cartResult->num_rows === 1) {
                        // Update quantity
                        $stmt3 = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE customer_id = ? AND product_id = ?");
                        $stmt3->bind_param("iii", $quantity, $customer_id, $product_id);
                        $stmt3->execute();
                        $stmt3->close();
                    } else {
                        // Insert new
                        $stmt3 = $conn->prepare("INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)");
                        $stmt3->bind_param("iii", $customer_id, $product_id, $quantity);
                        $stmt3->execute();
                        $stmt3->close();
                    }
                    $success = "Added to cart!";
                } else {
                    $error = "Not enough stock.";
                }
            } else {
                $error = "Product not found.";
            }
            $stmt->close();
        } else {
            $error = "Invalid product or quantity.";
        }
    } else {
        $error = "Please login to add to cart.";
    }
    // Redirect back to product page
    $redirect = '../product.php?id=' . intval($_POST['product_id']);
    if (isset($success)) {
        header('Location: ' . $redirect . '&success=' . urlencode($success));
    } else {
        header('Location: ' . $redirect . '&error=' . urlencode($error));
    }
    exit();
}
?>