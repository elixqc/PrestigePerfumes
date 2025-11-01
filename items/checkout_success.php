<?php
session_start();
require_once('../includes/config.php');

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../user/login.php");
    exit();
}

$order_id = $_GET['order_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Success - Prestige Perfumery</title>
<link rel="stylesheet" href="/prestigeperfumes/assets/css/style.css">
</head>
<body>
<?php include('../includes/header.php'); ?>

<section class="checkout-success">
    <div class="container">
        <h1>Thank You!</h1>
        <p>Your order #<?php echo htmlspecialchars($order_id); ?> has been successfully placed.</p>
        <a href="/prestigeperfumes/items/index.php" class="btn btn-primary"><span>Continue Shopping</span></a>
    </div>
</section>

<?php include('../includes/footer.php'); ?>

<style>
.checkout-success {
    text-align: center;
    padding: 100px 20px;
    font-family: 'Montserrat', sans-serif;
}
.checkout-success h1 {
    font-family: 'Playfair Display', serif;
    font-size: 48px;
    margin-bottom: 20px;
}
.checkout-success p {
    font-size: 18px;
    color: #333;
}
</style>
</body>
</html>
