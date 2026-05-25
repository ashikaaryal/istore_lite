<?php
require_once 'includes/auth.php';
requireSiteLogin();

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart']) && isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $productId => $qty) {
            $productId = (int)$productId;
            $qty = (int)$qty;

            if (isset($_SESSION['cart'][$productId])) {
                $product = fetchProductById($conn, $productId);

                if (!$product) {
                    unset($_SESSION['cart'][$productId]);
                    continue;
                }

                if ($qty <= 0) {
                    unset($_SESSION['cart'][$productId]);
                } elseif ($qty <= (int)$product['stock']) {
                    $_SESSION['cart'][$productId]['quantity'] = $qty;
                } else {
                    $_SESSION['cart'][$productId]['quantity'] = (int)$product['stock'];
                    $error = "Some quantities were adjusted based on available stock.";
                }
            }
        }
        $message = $message ?: "Cart updated successfully.";
    }

    if (isset($_POST['remove_item'])) {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
            $message = "Item removed from cart.";
        }
    }
}

$cartItems = $_SESSION['cart'];
$total = cartGrandTotal();
$cartCount = getCartCount();
$userName = getCurrentUserName();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - iStore Lite</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page-bg"></div>

<header class="navbar glass">
    <div class="nav-left">
        <a href="index.php" class="logo">iStore <span>lite</span></a>
        <nav class="nav-links">
            <a href="index.php">Store</a>
            <a href="cart.php" class="active">Cart <span class="badge"><?php echo $cartCount; ?></span></a>
            <?php if (isAdmin()): ?>
                <a href="admin/dashboard.php">Admin</a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="nav-right">
        <div class="profile-pill">
            <span class="profile-avatar"><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
            <span><?php echo sanitize($userName); ?><?php echo isAdmin() ? ' (Admin)' : ''; ?></span>
        </div>
        <a href="logout.php" class="btn btn-outline">Logout</a>
    </div>
</header>

<main class="container">
    <div class="section-head">
        <h2>Your Cart</h2>
        <p>Review your products before checkout.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert success"><?php echo sanitize($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert error"><?php echo sanitize($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($cartItems)): ?>
        <div class="cart-layout">
            <div class="cart-items">
                <form method="POST">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-card glass">
                            <div class="cart-card-image">
                                <img src="<?php echo sanitize($item['image']); ?>" alt="<?php echo sanitize($item['name']); ?>">
                            </div>

                            <div class="cart-card-content">
                                <h3><?php echo sanitize($item['name']); ?></h3>
                                <p class="muted">Unit Price: $<?php echo number_format($item['price'], 2); ?></p>

                                <div class="cart-meta-row">
                                    <div class="form-group inline-group">
                                        <label>Qty</label>
                                            <input type="number" name="quantities[<?php echo (int)$item['id']; ?>]" min="1" max="<?php echo (int)$item['stock']; ?>" value="<?php echo (int)$item['quantity']; ?>" required>
                                    <div class="cart-subtotal">
                                        <span>Subtotal</span>
                                        <strong>Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                    </div>
                                </div>

                                <div class="cart-actions">
                                    <button type="submit" name="update_cart" class="btn btn-primary btn-sm">Update</button>
                                </div>
                            </div>

                            <div class="cart-remove">
                                <button type="submit" form="remove-<?php echo (int)$item['id']; ?>" class="btn btn-danger btn-sm">Remove</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </form>

                <?php foreach ($cartItems as $item): ?>
                    <form method="POST" id="remove-<?php echo (int)$item['id']; ?>" style="display:none;">
                        <input type="hidden" name="product_id" value="<?php echo (int)$item['id']; ?>">
                        <input type="hidden" name="remove_item" value="1">
                    </form>
                <?php endforeach; ?>
            </div>

            <aside class="summary-card glass">
                <h3>Order Summary</h3>
                <div class="summary-row">
                    <span>Items</span>
                    <strong><?php echo $cartCount; ?></strong>
                </div>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong>Rs. <?php echo number_format($total, 2); ?></strong>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <strong>Free</strong>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <strong>Rs. <?php echo number_format($total, 2); ?></strong>
                </div>

                <a href="checkout.php" class="btn btn-primary btn-block">Proceed to Checkout</a>
            </aside>
        </div>
    <?php else: ?>
        <div class="glass empty-state">
            <h3>Your cart is empty</h3>
            <p>Add products from the store to continue.</p>
            <a href="index.php" class="btn btn-primary">Continue Shopping</a>
        </div>
    <?php endif; ?>
</main>
</body>
</html>