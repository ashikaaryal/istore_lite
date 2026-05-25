<?php
require_once 'includes/auth.php';
requireLogin();

$orderId = (int)($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
    die("Invalid order.");
}

$orderQuery = mysqli_query($conn, "SELECT * FROM orders WHERE id = $orderId");
$order = mysqli_fetch_assoc($orderQuery);

if (!$order) {
    die("Order not found.");
}

$amountPaisa = (int)($order['total_amount'] * 100);

$payload = [
    "return_url" => "http://localhost/istore-lite/khalti_success.php",
    "website_url" => "http://localhost/istore-lite/",
    "amount" => $amountPaisa,
    "purchase_order_id" => (string)$orderId,
    "purchase_order_name" => "iStore Lite Order #" . $orderId
];

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://dev.khalti.com/api/v2/epayment/initiate/",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        "Authorization: Key YOUR_KHALTI_SECRET_KEY",
        "Content-Type: application/json"
    ],
]);

$response = curl_exec($curl);
curl_close($curl);

$result = json_decode($response, true);

if (isset($result['payment_url'])) {
    header("Location: " . $result['payment_url']);
    exit;
}

echo "Unable to start Khalti payment.";