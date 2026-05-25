<?php
require_once '../includes/auth.php';
requireAdmin();

$userName = getCurrentUserName();
$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $status = trim($_POST['status'] ?? '');

    $allowedStatuses = ['Pending', 'Paid', 'Processing', 'Completed', 'Cancelled'];

    if ($orderId <= 0 || !in_array($status, $allowedStatuses, true)) {
        $error = "Invalid order status update request.";
    } else {
        $sql = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $status, $orderId);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Order status updated successfully.";
        } else {
            $error = "Failed to update order status.";
        }

        mysqli_stmt_close($stmt);
    }
}

$orders = [];

$sql = "SELECT orders.*, users.name AS user_name
        FROM orders
        INNER JOIN users ON orders.user_id = users.id
        ORDER BY orders.created_at DESC";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - iStore Lite</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="page-bg"></div>

<div class="admin-shell">
    <aside class="admin-sidebar glass">
        <a href="../index.php" class="brand">iStore <span>lite</span></a>

        <div class="admin-user">
            <div class="profile-pill">
                <span class="profile-avatar"><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
                <span><?php echo sanitize($userName); ?></span>
            </div>
        </div>

        <nav class="admin-nav">
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="manage_products.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_products.php' ? 'active' : ''; ?>">Manage Products</a>
            <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>">Manage Orders</a>
            <a href="../index.php">View Store</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="section-head">
            <h2>Manage Orders</h2>
            <p>View all orders and update their status.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert success"><?php echo sanitize($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?php echo sanitize($error); ?></div>
        <?php endif; ?>

        <div class="glass table-card">
            <h3>All Orders</h3>

            <?php if (!empty($orders)): ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-top">
                                <div>
                                    <h3><?php echo sanitize($order['order_number']); ?></h3>
                                    <p class="muted">
                                        Customer: <?php echo sanitize($order['customer_name']); ?> (<?php echo sanitize($order['customer_email']); ?>)
                                    </p>
                                </div>
                                <span class="status-pill"><?php echo sanitize($order['status']); ?></span>
                            </div>

                            <div class="order-grid">
                                <div>
                                    <span class="muted">User</span>
                                    <strong><?php echo sanitize($order['user_name']); ?></strong>
                                </div>
                                <div>
                                    <span class="muted">Phone</span>
                                    <strong><?php echo sanitize($order['phone']); ?></strong>
                                </div>
                                <div>
                                    <span class="muted">Total</span>
                                    <strong>Rs.<?php echo number_format($order['total_amount'], 2); ?></strong>
                                </div>
                                <div>
                                    <span class="muted">Created</span>
                                    <strong><?php echo sanitize($order['created_at']); ?></strong>
                                </div>
                            </div>

                            <div class="order-address">
                                <span class="muted">Address</span>
                                <p><?php echo sanitize($order['address']); ?></p>
                            </div>

                            <?php
                            $itemsSql = "SELECT product_name, quantity, subtotal FROM order_items WHERE order_id = ?";
                            $itemsStmt = mysqli_prepare($conn, $itemsSql);
                            mysqli_stmt_bind_param($itemsStmt, "i", $order['id']);
                            mysqli_stmt_execute($itemsStmt);
                            $itemsRes = mysqli_stmt_get_result($itemsStmt);
                            ?>
                            <div class="order-items-box">
                                <?php while ($item = mysqli_fetch_assoc($itemsRes)): ?>
                                    <div class="summary-row">
                                        <span><?php echo sanitize($item['product_name']); ?> × <?php echo (int)$item['quantity']; ?></span>
                                        <strong>Rs.<?php echo number_format($item['subtotal'], 2); ?></strong>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <?php mysqli_stmt_close($itemsStmt); ?>

                            <div style="margin-top: 18px;">
                                <form method="POST" class="status-form">
                                    <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">

                                    <div class="form-group">
                                        <label for="status_<?php echo (int)$order['id']; ?>">Update Status</label>
                                        <select name="status" id="status_<?php echo (int)$order['id']; ?>" class="status-select" required>
                                            <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Paid" <?php echo $order['status'] === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                            <option value="Processing" <?php echo $order['status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="Completed" <?php echo $order['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Cancelled" <?php echo $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>

                                    <button type="submit" name="update_status" class="btn btn-primary btn-sm">Save Status</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted">No orders found.</p>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>