<?php

require_once 'includes/auth.php';
require_once 'esewa.php';

requireLogin();

$orderId = isset($_GET['order_id'])
    ? (int)$_GET['order_id']
    : 0;

if ($orderId <= 0) {
    die("Invalid Order ID");
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM orders
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $orderId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$order = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$order) {
    die("Order not found");
}

$transactionUuid = $order['order_number'];

$productCode = "EPAYTEST";

$totalAmount = sprintf(
    "%.2f",
    (float)$order['total_amount']
);

$signature = generateEsewaSignature(
    $totalAmount,
    $transactionUuid,
    $productCode
);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to eSewa</title>
</head>
<body>

<h3>Redirecting to eSewa...</h3>

<form
    id="esewaForm"
   action="https://rc-epay.esewa.com.np/api/epay/main/v2/form"
    method="POST"
>

    <input
        type="hidden"
        name="amount"
        value="<?= $totalAmount ?>"
    >

    <input
        type="hidden"
        name="tax_amount"
        value="0"
    >

    <input
        type="hidden"
        name="total_amount"
        value="<?= $totalAmount ?>"
    >

    <input
        type="hidden"
        name="transaction_uuid"
        value="<?= htmlspecialchars($transactionUuid) ?>"
    >

    <input
        type="hidden"
        name="product_code"
        value="<?= $productCode ?>"
    >

    <input
        type="hidden"
        name="product_service_charge"
        value="0"
    >

    <input
        type="hidden"
        name="product_delivery_charge"
        value="0"
    >

    <!-- Replace with your public URL -->
    <input
    type="hidden"
    name="success_url"
    value="http://localhost/istore-lite/esewa_success.php"
>

<input
    type="hidden"
    name="failure_url"
    value="http://localhost/istore-lite/esewa_failed.php"
>


    <input
        type="hidden"
        name="signed_field_names"
        value="total_amount,transaction_uuid,product_code"
    >

    <input
        type="hidden"
        name="signature"
        value="<?= htmlspecialchars($signature) ?>"
    >

    <noscript>
        <button type="submit">
            Pay with eSewa
        </button>
    </noscript>

</form>

<script>
document.getElementById('esewaForm').submit();
</script>

</body>
</html>