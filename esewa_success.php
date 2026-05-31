<?php

require_once 'includes/auth.php';

$data = $_GET['data'] ?? '';

if (!$data) {
    die("Invalid payment response");
}

$response = json_decode(
    base64_decode($data),
    true
);

if (!$response) {
    die("Invalid response");
}
$status = $response['status'];
$transaction_uuid = $response['transaction_uuid'];

if ($status === 'COMPLETE') {

    $sql = "
        UPDATE orders
        SET
            payment_status='paid',
            status='Confirmed'
        WHERE order_number=?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $transaction_uuid
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    /*
     * NOW REDUCE STOCK
     */

    $orderSql = "
        SELECT *
        FROM orders
        WHERE order_number=?
        LIMIT 1
    ";

    $stmtOrder = mysqli_prepare($conn, $orderSql);

    mysqli_stmt_bind_param(
        $stmtOrder,
        "s",
        $transaction_uuid
    );

    mysqli_stmt_execute($stmtOrder);

    $result = mysqli_stmt_get_result($stmtOrder);

    $order = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmtOrder);

    $orderId = $order['id'];

    $itemsSql = "
        SELECT *
        FROM order_items
        WHERE order_id=?
    ";

    $stmtItems = mysqli_prepare($conn, $itemsSql);

    mysqli_stmt_bind_param(
        $stmtItems,
        "i",
        $orderId
    );

    mysqli_stmt_execute($stmtItems);

    $itemsResult = mysqli_stmt_get_result($stmtItems);

    while ($item = mysqli_fetch_assoc($itemsResult)) {

        $updateStock = "
            UPDATE products
            SET stock = stock - ?
            WHERE id = ?
        ";

        $stmtStock = mysqli_prepare($conn, $updateStock);

        mysqli_stmt_bind_param(
            $stmtStock,
            "ii",
            $item['quantity'],
            $item['product_id']
        );

        mysqli_stmt_execute($stmtStock);

        mysqli_stmt_close($stmtStock);
    }

    mysqli_stmt_close($stmtItems);

    $_SESSION['cart'] = [];

    header("Location: order_success.php?order_id=" . $orderId);
    exit;

} else {

    die("Payment failed");
}