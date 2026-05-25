<?php
require_once 'includes/auth.php';
requireLogin();

if (!isset($_GET['data'])) {
    die("Payment verification failed.");
}

$data = base64_decode($_GET['data']);

$result = json_decode($data, true);

if (!$result) {
    die("Invalid payment response.");
}

$transactionUuid = $result['transaction_uuid'] ?? '';
$status = $result['status'] ?? '';

if ($status === 'COMPLETE') {

    mysqli_query(
        $conn,
        "UPDATE orders
         SET payment_status='paid',
             status='Confirmed'
         WHERE order_number='$transactionUuid'"
    );

    $_SESSION['cart'] = [];

    $query = mysqli_query(
        $conn,
        "SELECT id FROM orders
         WHERE order_number='$transactionUuid'
         LIMIT 1"
    );

    $order = mysqli_fetch_assoc($query);

    header("Location: order_success.php?order_id=" . $order['id']);
    exit;

} else {

    echo "Payment not completed.";
}
?>