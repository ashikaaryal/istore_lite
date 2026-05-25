<?php
require_once 'includes/auth.php';
requireLogin();

$pidx = $_GET['pidx'] ?? '';
$status = $_GET['status'] ?? '';
$transactionId = $_GET['transaction_id'] ?? '';
$orderId = (int)($_GET['purchase_order_id'] ?? 0);

if ($orderId <= 0) {
    die("Invalid order.");
}

if ($status === 'Completed') {
    $stmt = mysqli_prepare($conn, "
        UPDATE orders
        SET payment_status = 'paid', transaction_id = ?
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($stmt, "si", $transactionId, $orderId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $_SESSION['cart'] = [];

    header("Location: order_success.php?order_id=" . $orderId);
    exit;
}

mysqli_query($conn, "UPDATE orders SET payment_status='failed' WHERE id=$orderId");

header("Location: payment_failed.php?order_id=" . $orderId);
exit;