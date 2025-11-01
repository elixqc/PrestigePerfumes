<?php
// notification.php — returns user notifications in JSON
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/prestigeperfumes/includes/config.php');

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode([]);
    exit;
}

$customer_id = (int) $_SESSION['customer_id'];

// Fetch latest notifications for this user
$sql = "SELECT notification_id, message, is_read, date_created
        FROM notifications
        WHERE customer_id = ?
        ORDER BY date_created DESC
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'notification_id' => $row['notification_id'],
        'message' => $row['message'],
        'is_read' => (int) $row['is_read'],
        'date_created' => date("M d, Y h:i A", strtotime($row['date_created']))
    ];
}

$stmt->close();

// Mark all as read (optional)
$update = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE customer_id = ?");
$update->bind_param("i", $customer_id);
$update->execute();
$update->close();

// Return JSON
echo json_encode($notifications);
?>
