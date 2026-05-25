<?php
require_once 'includes/auth.php';
requireLogin();

// Check order id exists
if (
    !isset($_GET['order_id']) ||
    !is_numeric($_GET['order_id'])
) {
    die("Invalid Order ID");
}

$orderId = (int)$_GET['order_id'];

// Fetch order
$sql = "
    SELECT *
    FROM orders
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $orderId);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$order = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

// Order not found
if (!$order) {
    die("Order not found");
}

$total = (float)$order['total_amount'];

$transactionUuid = $order['order_number'];

$productCode = "EPAYTEST";

// Generate eSewa signature
$signature = generateEsewaSignature(
    $total,
    $transactionUuid,
    $productCode
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eSewa Payment</title>
</head>
<body>

<h2>Redirecting to eSewa...</h2>

<form
    id="esewaForm"
    action="https://rc-epay.esewa.com.np/api/epay/main/v2/form"
    method="POST"
>

    <input
        type="hidden"
        name="amount"
        value="<?php echo $total; ?>"
    >

    <input
        type="hidden"
        name="tax_amount"
        value="0"
    >

    <input
        type="hidden"
        name="total_amount"
        value="<?php echo $total; ?>"
    >

    <input
        type="hidden"
        name="transaction_uuid"
        value="<?php echo $transactionUuid; ?>"
    >

    <input
        type="hidden"
        name="product_code"
        value="<?php echo $productCode; ?>"
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

    <input
        type="hidden"
        name="success_url"
        value="http://localhost/istore/esewa_success.php"
    >

    <input
        type="hidden"
        name="failure_url"
        value="http://localhost/istore/esewa_failed.php"
    >

    <input
        type="hidden"
        name="signed_field_names"
        value="total_amount,transaction_uuid,product_code"
    >

    <input
        type="hidden"
        name="signature"
        value="<?php echo $signature; ?>"
    >

</form>

<script>
document.getElementById("esewaForm").submit();
</script>

</body>
</html>