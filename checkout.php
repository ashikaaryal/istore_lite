<?php
require_once 'includes/auth.php';
requireLogin();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$total = cartGrandTotal();

$userName  = $_SESSION['user_name'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = sanitize(trim($_POST['customer_name'] ?? ''));
    $email   = filter_var(trim($_POST['customer_email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone   = preg_replace('/\D/', '', $_POST['phone'] ?? '');
    $address = sanitize(trim($_POST['address'] ?? ''));
    $method  = $_POST['payment_method'] ?? 'cod';

    $allowedMethods = ['cod', 'esewa'];

    if (!in_array($method, $allowedMethods)) {
        $method = 'cod';
    }

    if (!$name || !$email || !$address) {
        $error = "Please fill all fields correctly.";
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $error = "Phone number must be 10 digits.";
    } else {

        try {

            mysqli_begin_transaction($conn);

            $orderNumber = 'ORD-' . strtoupper(bin2hex(random_bytes(5)));
            $userId = (int)$_SESSION['user_id'];

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO orders
                (
                    user_id,
                    order_number,
                    total_amount,
                    status,
                    customer_name,
                    customer_email,
                    phone,
                    address,
                    payment_method,
                    payment_status
                )
                VALUES
                (
                    ?, ?, ?, 'Pending',
                    ?, ?, ?, ?,
                    ?, 'pending'
                )"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "isdsssss",
                $userId,
                $orderNumber,
                $total,
                $name,
                $email,
                $phone,
                $address,
                $method
            );

            mysqli_stmt_execute($stmt);

            $orderId = mysqli_insert_id($conn);

            mysqli_stmt_close($stmt);

            foreach ($_SESSION['cart'] as $item) {

                $productId   = (int)$item['id'];
                $productName = $item['name'];
                $price       = (float)$item['price'];
                $quantity    = (int)$item['quantity'];
                $subtotal    = $price * $quantity;

                // Check stock
                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT stock FROM products WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $productId
                );

                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);

                $product = mysqli_fetch_assoc($result);

                mysqli_stmt_close($stmt);

                if (!$product) {
                    throw new Exception("Product not found.");
                }

                if ($product['stock'] < $quantity) {
                    throw new Exception(
                        $productName . " does not have enough stock."
                    );
                }

                // Insert order item
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO order_items
                    (
                        order_id,
                        product_id,
                        product_name,
                        price,
                        quantity,
                        subtotal
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?, ?
                    )"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "iisdid",
                    $orderId,
                    $productId,
                    $productName,
                    $price,
                    $quantity,
                    $subtotal
                );

                mysqli_stmt_execute($stmt);

                mysqli_stmt_close($stmt);

                // Update stock
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE products
                     SET stock = stock - ?
                     WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "ii",
                    $quantity,
                    $productId
                );

                mysqli_stmt_execute($stmt);

                mysqli_stmt_close($stmt);
            }

            mysqli_commit($conn);

            if ($method === 'esewa') {

                header(
                    "Location: esewa_payment.php?order_id=" .
                    $orderId
                );

            } else {

                $_SESSION['cart'] = [];

                header(
                    "Location: order_success.php?order_id=" .
                    $orderId
                );
            }

            exit;

        } catch (Exception $e) {

            mysqli_rollback($conn);

            error_log($e->getMessage());

            $error = "Unable to complete order. Please try again.";
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
                        value="<?php echo sanitize($userName); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="customer_email">Email</label>
                    <input
                        type="email"
                        id="customer_email"
                        name="customer_email"
                        value="<?php echo sanitize($userEmail); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        pattern="[0-9]{10}"
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
                        <?php echo number_format(
                            $item['price'] * $item['quantity'],
                            2
                        ); ?>
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