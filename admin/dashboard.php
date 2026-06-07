<?php
require_once '../includes/auth.php';
requireAdmin();

$userName = getCurrentUserName();

$productCount = 0;
$userCount = 0;
$orderCount = 0;
$totalRevenue = 0;

$res1 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products");
if ($res1) {
    $productCount = (int)mysqli_fetch_assoc($res1)['total'];
}

$res2 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'user'");
if ($res2) {
    $userCount = (int)mysqli_fetch_assoc($res2)['total'];
}

$res3 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders");
if ($res3) {
    $orderCount = (int)mysqli_fetch_assoc($res3)['total'];
}

$res4 = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) AS revenue FROM orders");
if ($res4) {
    $totalRevenue = (float)mysqli_fetch_assoc($res4)['revenue'];
}

$statusLabels = [];
$statusData = [];
$statusQuery = mysqli_query($conn, "SELECT status, COUNT(*) AS total FROM orders GROUP BY status");
if ($statusQuery) {
    while ($row = mysqli_fetch_assoc($statusQuery)) {
        $statusLabels[] = $row['status'];
        $statusData[] = (int)$row['total'];
    }
}

$topProductsLabels = [];
$topProductsData = [];
$topProductsSql = "
    SELECT product_name, SUM(quantity) AS qty
    FROM order_items
    GROUP BY product_name
    ORDER BY qty DESC
    LIMIT 5
";
$topProductsQuery = mysqli_query($conn, $topProductsSql);
if ($topProductsQuery) {
    while ($row = mysqli_fetch_assoc($topProductsQuery)) {
        $topProductsLabels[] = $row['product_name'];
        $topProductsData[] = (int)$row['qty'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - iStore Lite</title>
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <h2>Admin Dashboard</h2>
        </div>

        <div class="stats-grid">
            <div class="stat-card glass">
                <span>Total Products</span>
                <strong><?php echo $productCount; ?></strong>
            </div>
            <div class="stat-card glass">
                <span>Total Customers</span>
                <strong><?php echo $userCount; ?></strong>
            </div>
            <div class="stat-card glass">
                <span>Total Orders</span>
                <strong><?php echo $orderCount; ?></strong>
            </div>
            <div class="stat-card glass">
                <span>Total Revenue</span>
                <strong>Rs.<?php echo number_format($totalRevenue, 2); ?></strong>
            </div>
        </div>

        <div class="charts-grid">
            <div class="glass chart-card">
                <h3>Orders by Status</h3>
                <canvas id="statusChart"></canvas>
            </div>

            <div class="glass chart-card">
                <h3>Top Selling Products</h3>
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
    </main>
</div>

<script>
const statusCtx = document.getElementById('statusChart');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($statusLabels); ?>,
        datasets: [{
            label: 'Orders',
            data: <?php echo json_encode($statusData); ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                labels: {
                    color: '#ffffff'
                }
            }
        }
    }
});

const topProductsCtx = document.getElementById('topProductsChart');
new Chart(topProductsCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($topProductsLabels); ?>,
        datasets: [{
            label: 'Units Sold',
            data: <?php echo json_encode($topProductsData); ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: {
                ticks: { color: '#ffffff' },
                grid: { color: 'rgba(255,255,255,0.08)' }
            },
            y: {
                beginAtZero: true,
                ticks: { color: '#ffffff' },
                grid: { color: 'rgba(255,255,255,0.08)' }
            }
        },
        plugins: {
            legend: {
                labels: {
                    color: '#ffffff'
                }
            }
        }
    }
});
</script>
</body>
</html>