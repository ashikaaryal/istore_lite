<?php
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

if (isAdmin()) {
    header("Location: ../admin/dashboard.php");
    exit;
}

$userName = getCurrentUserName();
$cartCount = getCartCount();

$userId = (int)$_SESSION['user_id'];
$orders = [];

$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = $row;
}

mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - iStore Lite</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="page-bg"></div>

<header class="navbar glass">
    <div class="nav-left">
        <a href="../index.php" class="logo">iStore <span>lite</span></a>
        <nav class="nav-links">
            <a href="../index.php">Store</a>
            <a href="../cart.php">Cart <span class="badge"><?php echo $cartCount; ?></span></a>
            <a href="dashboard.php" class="active">Dashboard</a>
        </nav>
    </div>

    <div class="nav-right">
        <div class="profile-pill">
            <span class="profile-avatar"><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
            <span><?php echo sanitize($userName); ?></span>
        </div>
        <a href="../logout.php" class="btn btn-outline">Logout</a>
    </div>
</header>

<main class="container">
    <div class="section-head">
        <h2>User Dashboard</h2>
        <p>Welcome back, <?php echo sanitize($userName); ?>.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card glass">
            <span>Total Orders</span>
            <strong><?php echo count($orders); ?></strong>
        </div>
        <div class="stat-card glass">
            <span>Cart Items</span>
            <strong><?php echo $cartCount; ?></strong>
        </div>
    </div>

    <div class="glass table-card">
        <h3>Your Orders</h3>

        <?php if (!empty($orders)): ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-top">
                            <div>
                                <h3><?php echo sanitize($order['order_number']); ?></h3>
                                <p class="muted"><?php echo sanitize($order['customer_email']); ?></p>
                            </div>
                            <span class="status-pill"><?php echo sanitize($order['status']); ?></span>
                        </div>

                        <div class="order-grid">
                            <div>
                                <span class="muted">Total</span>
                                <strong>$<?php echo number_format($order['total_amount'], 2); ?></strong>
                            </div>
                            <div>
                                <span class="muted">Phone</span>
                                <strong><?php echo sanitize($order['phone']); ?></strong>
                            </div>
                            <div>
                                <span class="muted">Date</span>
                                <strong><?php echo sanitize($order['created_at']); ?></strong>
                            </div>
                            <div>
                                <span class="muted">Address</span>
                                <strong><?php echo sanitize($order['address']); ?></strong>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="muted">No orders found yet.</p>
        <?php endif; ?>
    </div>
</main>
</body>
</html>