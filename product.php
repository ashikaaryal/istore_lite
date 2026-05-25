<?php
require_once 'includes/auth.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = fetchProductById($conn, $productId);

if (!$product) {
    header("Location: index.php");
    exit;
}

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }

    $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($qty < 1) {
        $qty = 1;
    }

    if ($qty > (int)$product['stock']) {
        $error = "Requested quantity exceeds available stock.";
    } else {
        if (isset($_SESSION['cart'][$productId])) {
            $newQty = $_SESSION['cart'][$productId]['quantity'] + $qty;

            if ($newQty > (int)$product['stock']) {
                $error = "Cannot add more than available stock.";
            } else {
                $_SESSION['cart'][$productId]['quantity'] = $newQty;
                $message = "Product added to cart successfully.";
            }
        } else {
            $_SESSION['cart'][$productId] = [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'price' => (float)$product['price'],
                'image' => $product['image'],
                'quantity' => $qty,
                'stock' => (int)$product['stock']
            ];
            $message = "Product added to cart successfully.";
        }
    }
}

$cartCount = getCartCount();
$userName = getCurrentUserName();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($product['name']); ?> - iStore Lite</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page-bg"></div>

<header class="navbar glass">
    <div class="nav-left">
        <a href="index.php" class="logo">iStore <span>lite</span></a>
        <nav class="nav-links">
            <a href="index.php">Store</a>
            <a href="cart.php">Cart <span class="badge"><?php echo $cartCount; ?></span></a>
            <?php if (isLoggedIn() && isAdmin()): ?>
                <a href="admin/dashboard.php">Admin</a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="nav-right">
        <?php if (isLoggedIn()): ?>
            <div class="profile-pill">
                <span class="profile-avatar"><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
                <span><?php echo sanitize($userName); ?><?php echo isAdmin() ? ' (Admin)' : ''; ?></span>
            </div>
            <a href="logout.php" class="btn btn-outline">Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-outline">Login</a>
            <a href="register.php" class="btn btn-primary">Register</a>
        <?php endif; ?>
    </div>
</header>

<main class="container">
    <?php if ($message): ?>
        <div class="alert success"><?php echo sanitize($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert error"><?php echo sanitize($error); ?></div>
    <?php endif; ?>

    <div class="product-detail glass">
        <div class="product-detail-image">
            <img src="<?php echo sanitize($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>">
        </div>

        <div class="product-detail-info">
            <span class="eyebrow">Premium Selection</span>
            <h1><?php echo sanitize($product['name']); ?></h1>
            <p class="detail-price">$<?php echo number_format($product['price'], 2); ?></p>

            <div class="spec-list">
                <div class="spec-card glass">
                    <span class="spec-label">Storage</span>
                    <strong><?php echo sanitize($product['storage']); ?></strong>
                </div>
                <div class="spec-card glass">
                    <span class="spec-label">Color</span>
                    <strong><?php echo sanitize($product['color']); ?></strong>
                </div>
                <div class="spec-card glass">
                    <span class="spec-label">Stock</span>
                    <strong><?php echo (int)$product['stock']; ?></strong>
                </div>
            </div>

            <p class="detail-desc">
                <?php echo nl2br(sanitize($product['description'] ?: 'No description available for this product.')); ?>
            </p>

            <?php if (isLoggedIn()): ?>
                <form method="POST" class="add-cart-form">
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" name="quantity" id="quantity" min="1" max="<?php echo (int)$product['stock']; ?>" value="1" required>
                    </div>
                    <div class="detail-actions">
                        <button type="submit" class="btn btn-primary">Add to Cart</button>
                        <a href="cart.php" class="btn btn-outline">Go to Cart</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="detail-actions">
                    <a href="login.php" class="btn btn-primary">Login to Add to Cart</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>