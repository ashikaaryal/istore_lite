<?php
require_once 'includes/auth.php';
requireLogin();

$orderId = (int)($_GET['order_id'] ?? 0);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Failed</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container">
    <div class="glass empty-state">
        <h2>Payment Failed or Cancelled</h2>
        <p>Your payment was not completed.</p>
        <a href="checkout.php" class="btn btn-primary">Try Again</a>
        <a href="cart.php" class="btn btn-outline">Back to Cart</a>
    </div>
</main>
</body>
</html>