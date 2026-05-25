<?php
require_once 'includes/auth.php';
requireLogin();

$orderId = (int)($_GET['order_id'] ?? 0);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order Success</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container">
    <div class="glass empty-state">
        <h2>Order Placed Successfully!!</h2>
        <p>Your order ID is #<?php echo $orderId; ?></p>
        <a href="index.php" class="btn btn-primary">Back to Store</a>
    </div>
</main>
</body>
</html>