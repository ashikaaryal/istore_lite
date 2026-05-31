<?php
require_once 'includes/auth.php';
requireLogin();

if (empty($_SESSION['cart'])) {
    header("Location: esewa_payment.php");
    exit;
}
$message = "";
$error = "";
$userName = getCurrentUserName();
$cartCount = getCartCount();
$total = cartGrandTotal();

$defaultName = $_SESSION['user_name'] ?? '';
$defaultEmail = $_SESSION['user_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $customerName = sanitize(trim($_POST['customer_name'] ?? ''));
    $customerEmailRaw = trim($_POST['customer_email'] ?? '');
    $customerEmail = filter_var($customerEmailRaw, FILTER_VALIDATE_EMAIL);

    $phoneRaw = trim($_POST['phone'] ?? '');
    $phone = preg_replace('/\D/', '', $phoneRaw);

    $address = sanitize(trim($_POST['address'] ?? ''));

    $paymentMethod = $_POST['payment_method'] ?? 'cod';

    // CHANGED HERE
    $allowedMethods = ['cod', 'esewa'];

    if (
        !$customerName ||
        !$customerEmailRaw ||
        !$customerEmail ||
        !$phone ||
        !$address ||
        !in_array($paymentMethod, $allowedMethods, true)
    ) {

        $error = "Please fill in all required fields correctly.";

    } elseif (!preg_match('/^[A-Za-z ]+$/', $customerName)) {

        $error = "Name can contain only letters and spaces.";

    } elseif (!preg_match('/^\d{10}$/', $phone)) {

        $error = "Please enter a valid 10-digit phone number.";

    } else {

        mysqli_begin_transaction($conn);

        try {

            foreach ($_SESSION['cart'] as $item) {

                $dbProduct = fetchProductById($conn, (int)$item['id']);

                if (
                    !$dbProduct ||
                    (int)$dbProduct['stock'] < (int)$item['quantity']
                ) {
                    throw new Exception(
                        "One or more items are out of stock."
                    );
                }
            }

            $orderNumber = 'ORD-' . time() . '-' . rand(1000, 9999);

            $userId = (int)$_SESSION['user_id'];

            // INSERT ORDER
            $insertOrder = "
                INSERT INTO orders
                (
                    user_id,
                    order_number,
                    total_amount,
                    status,
                    customer_name,
                    customer_email,
                    phone,
                    address
                )
                VALUES
                (?, ?, ?, 'Pending', ?, ?, ?, ?)
            ";

            $stmtOrder = mysqli_prepare($conn, $insertOrder);

            mysqli_stmt_bind_param(
                $stmtOrder,
                "isdssss",
                $userId,
                $orderNumber,
                $total,
                $customerName,
                $customerEmail,
                $phone,
                $address
            );

            mysqli_stmt_execute($stmtOrder);

            if (mysqli_stmt_affected_rows($stmtOrder) <= 0) {
                throw new Exception("Failed to create order.");
            }

            $orderId = mysqli_insert_id($conn);

            mysqli_stmt_close($stmtOrder);

            // INSERT ORDER ITEMS
            $insertItem = "
                INSERT INTO order_items
                (
                    order_id,
                    product_id,
                    product_name,
                    price,
                    quantity,
                    subtotal
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ";

            $stmtItem = mysqli_prepare($conn, $insertItem);

            // UPDATE STOCK
            $updateStock = "
                UPDATE products
                SET stock = stock - ?
                WHERE id = ? AND stock >= ?
            ";

            $stmtStock = mysqli_prepare($conn, $updateStock);

            foreach ($_SESSION['cart'] as $item) {

                $productId = (int)$item['id'];
                $productName = $item['name'];
                $price = (float)$item['price'];
                $quantity = (int)$item['quantity'];
                $subtotal = $price * $quantity;

                mysqli_stmt_bind_param(
                    $stmtItem,
                    "iisdid",
                    $orderId,
                    $productId,
                    $productName,
                    $price,
                    $quantity,
                    $subtotal
                );

                mysqli_stmt_execute($stmtItem);

                mysqli_stmt_bind_param(
                    $stmtStock,
                    "iii",
                    $quantity,
                    $productId,
                    $quantity
                );

                mysqli_stmt_execute($stmtStock);

                if (mysqli_stmt_affected_rows($stmtStock) <= 0) {
                    throw new Exception(
                        "Failed to update stock for product ID " . $productId
                    );
                }
            }

            mysqli_stmt_close($stmtItem);
            mysqli_stmt_close($stmtStock);

            mysqli_commit($conn);

            // PAYMENT METHOD
            if ($paymentMethod === 'esewa') {

                mysqli_query(
                    $conn,
                    "UPDATE orders 
                     SET payment_method='esewa',
                         payment_status='pending'
                     WHERE id=$orderId"
                );

                header("Location: esewa_payment.php?order_id=" . $orderId);
                exit;

            } else {

                mysqli_query(
                    $conn,
                    "UPDATE orders 
                     SET payment_method='cod',
                         payment_status='pending'
                     WHERE id=$orderId"
                );

                $_SESSION['cart'] = [];

                header("Location: order_success.php?order_id=" . $orderId);
                exit;
            }

        } catch (Exception $e) {

            mysqli_rollback($conn);

            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - iStore Lite</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="page-bg"></div>

<header class="navbar glass">

    <div class="nav-left">

        <a href="index.php" class="logo">
             iStore Lite
        </a>

        <nav class="nav-links">

            <a href="index.php">Store</a>

            <a href="cart.php">
                Cart
                <span class="badge">
                    <?php echo getCartCount(); ?>
                </span>
            </a>

            <?php if (isAdmin()): ?>
                <a href="admin/dashboard.php">Admin</a>
            <?php endif; ?>

        </nav>

    </div>

    <div class="nav-right">

        <div class="profile-pill">

            <span class="profile-avatar">
                <?php echo strtoupper(substr($userName, 0, 1)); ?>
            </span>

            <span>
                <?php echo sanitize($userName); ?>
                <?php echo isAdmin() ? ' (Admin)' : ''; ?>
            </span>

        </div>

        <a href="logout.php" class="btn btn-outline">
            Logout
        </a>

    </div>

</header>

<main class="container">

    <div class="section-head">
        <h2>Checkout</h2>
        <p>Complete your order securely.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert error">
            <?php echo sanitize($error); ?>
        </div>
    <?php endif; ?>

    <div class="checkout-layout">

        <div class="form-card glass">

            <form method="POST">

                <div class="form-group">

                    <label for="customer_name">Full Name</label>

                    <input
                        type="text"
                        id="customer_name"
                        name="customer_name"
                        value="<?php echo sanitize($defaultName); ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="customer_email">Email</label>

                    <input
                        type="email"
                        id="customer_email"
                        name="customer_email"
                        value="<?php echo sanitize($defaultEmail); ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="phone">Phone</label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        pattern="\d{10}"
                        maxlength="10"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="address">Shipping Address</label>

                    <textarea
                        id="address"
                        name="address"
                        rows="5"
                        required
                    ></textarea>

                </div>

                <div class="form-group">

                    <label>Payment Method</label>

                    <label>
                        <input
                            type="radio"
                            name="payment_method"
                            value="cod"
                            checked
                        >
                        Cash on Delivery
                    </label>

                    <label>
                        <input
                            type="radio"
                            name="payment_method"
                            value="esewa"
                        >
                        eSewa
                    </label>

                </div>

                <button
                    type="submit"
                    class="btn btn-primary btn-block"
                >
                    Place Order
                </button>

            </form>

        </div>

        <aside class="summary-card glass">

            <h3>Order Summary</h3>

            <?php foreach ($_SESSION['cart'] as $item): ?>

                <div class="summary-row">

                    <span>
                        <?php echo sanitize($item['name']); ?>
                        ×
                        <?php echo (int)$item['quantity']; ?>
                    </span>

                    <strong>
                        Rs.
                        <?php
                        echo number_format(
                            $item['price'] * $item['quantity'],
                            2
                        );
                        ?>
                    </strong>

                </div>

            <?php endforeach; ?>

            <div class="summary-row total">

                <span>Total</span>

                <strong>
                    Rs. <?php echo number_format($total, 2); ?>
                </strong>

            </div>

        </aside>

    </div>

</main>

</body>
</html>