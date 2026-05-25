<?php
require_once 'includes/auth.php';

$categoryName = trim($_GET['name'] ?? '');

if ($categoryName === '') {
    header("Location: index.php");
    exit;
}

$sql = "SELECT * FROM products WHERE category = ? ORDER BY price ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $categoryName);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$products = [];
$availableColors = [];

while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
    if (!empty($row['color'])) {
        $availableColors[] = $row['color'];
    }
}

mysqli_stmt_close($stmt);

$availableColors = array_unique($availableColors);
sort($availableColors);

$cartCount = getCartCount();
$userName = getCurrentUserName();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($categoryName); ?> - iStore Lite</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="landing-body">

<div class="landing-bg"></div>

<header class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="brand">iStore <span>lite</span></a>

        <nav class="topnav">
            <a href="index.php">Home</a>
            <a href="index.php#featured">Shop</a>
            <a href="cart.php">Cart<?php if ($cartCount > 0): ?> <span class="nav-count"><?php echo $cartCount; ?></span><?php endif; ?></a>

            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <a href="admin/dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a href="user/dashboard.php">Dashboard</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container">
    <div class="section-head">
        <div>
            <h2><?php echo sanitize($categoryName); ?></h2>
            <p>Available models in this category.</p>
        </div>
        <a href="index.php" class="btn btn-outline">Back to Home</a>
    </div>

    <div class="glass category-summary-box">
        <h3>Available Colors</h3>
        <div class="color-chip-row">
            <?php if (!empty($availableColors)): ?>
                <?php foreach ($availableColors as $color): ?>
                    <span class="color-chip"><?php echo sanitize($color); ?></span>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="muted">No color information available.</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="product-grid" style="margin-top: 24px;">
        <?php foreach ($products as $product): ?>
            <div class="product-card glass">
                <div class="product-image-wrap">
                    <img src="<?php echo sanitize($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>" class="product-image">
                </div>

                <div class="product-content">
                    <h3><?php echo sanitize($product['name']); ?></h3>
                    <p class="muted"><?php echo sanitize($product['storage']); ?> • <?php echo sanitize($product['color']); ?></p>
                    <div class="product-bottom">
                        <span class="price">Rs.<?php echo number_format($product['price'], 0); ?></span>
                        <a href="product.php?id=<?php echo (int)$product['id']; ?>" class="btn btn-primary btn-sm">View</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($products)): ?>
            <div class="glass empty-state">
                <h3>No products found</h3>
                <p>No items are available in this category yet.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

</body>
</html>