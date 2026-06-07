<?php
require_once 'includes/auth.php';
requireLogin();

$data = $_GET['data'] ?? '';
$response = json_decode(base64_decode($data), true);

if (!$response || ($response['status'] ?? '') !== 'COMPLETE') {
    die("Payment failed");
}

$uuid = trim($response['transaction_uuid']);

$stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE order_number=?");
mysqli_stmt_bind_param($stmt, "s", $uuid);
mysqli_stmt_execute($stmt);

$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) die("Order not found");

$orderId = $order['id'];

mysqli_query($conn, "
    UPDATE orders 
    SET payment_status='paid', status='Confirmed'
    WHERE id=$orderId
");

$_SESSION['cart'] = [];

header("Location: order_success.php?order_id=" . $orderId);
exit;