<?php
// user/delete_account.php
session_start();
require_once('../includes/config.php');

// Ensure user logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = (int) $_SESSION['customer_id'];

// Only process when POST requested
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete account (this cascades to related tables per your SQL constraints)
    $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Delete prepare failed: " . $conn->error);
    }

    // Clean session & redirect to login
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}

// If accessed by GET, redirect back to profile
header("Location: index.php");
exit;
