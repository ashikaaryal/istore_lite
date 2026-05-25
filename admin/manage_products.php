<?php
require_once '../includes/auth.php';
requireAdmin();

$userName = getCurrentUserName();
$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = sanitize(trim($_POST['name'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $image = trim($_POST['image'] ?? '');
        $storage = sanitize(trim($_POST['storage'] ?? ''));
        $color = sanitize(trim($_POST['color'] ?? ''));
        $stock = (int)($_POST['stock'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if (!$name || $price <= 0 || !$image || !$storage || !$color || $stock < 0) {
            $error = "Please fill all fields correctly.";
        } else {
            $sql = "INSERT INTO products (name, price, image, storage, color, stock, description) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sdsssis", $name, $price, $image, $storage, $color, $stock, $description);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Product added successfully.";
            } else {
                $error = "Failed to add product.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitize(trim($_POST['name'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $image = trim($_POST['image'] ?? '');
        $storage = sanitize(trim($_POST['storage'] ?? ''));
        $color = sanitize(trim($_POST['color'] ?? ''));
        $stock = (int)($_POST['stock'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if ($id <= 0 || !$name || $price <= 0 || !$image || !$storage || !$color || $stock < 0) {
            $error = "Please fill all fields correctly.";
        } else {
            $sql = "UPDATE products SET name = ?, price = ?, image = ?, storage = ?, color = ?, stock = ?, description = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sdsssisi", $name, $price, $image, $storage, $color, $stock, $description, $id);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Product updated successfully.";
            } else {
                $error = "Failed to update product.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $sql = "DELETE FROM products WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Product deleted successfully.";
            } else {
                $error = "Failed to delete product.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$editProduct = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editProduct = fetchProductById($conn, $editId);
}

$products = fetchAllProducts($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - iStore Lite</title>
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
            <h2>Manage Products</h2>
            <p>Add, edit, and delete store products.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert success"><?php echo sanitize($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?php echo sanitize($error); ?></div>
        <?php endif; ?>

        <div class="admin-content-grid">
            <div class="glass form-card">
                <h3><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h3>

                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $editProduct ? 'update' : 'add'; ?>">
                    <?php if ($editProduct): ?>
                        <input type="hidden" name="id" value="<?php echo (int)$editProduct['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" value="<?php echo $editProduct ? sanitize($editProduct['name']) : ''; ?>" required minlength="2">
                    </div>

                    <div class="form-group">
                        <label>Price</label>
                        <input type="number" step="0.01" min="0.01" name="price" value="<?php echo $editProduct ? (float)$editProduct['price'] : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Image URL</label>
                        <input type="text" name="image" value="<?php echo $editProduct ? sanitize($editProduct['image']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Storage</label>
                        <input type="text" name="storage" value="<?php echo $editProduct ? sanitize($editProduct['storage']) : ''; ?>" required minlength="1">
                    </div>

                    <div class="form-group">
                        <label>Color</label>
                        <input type="text" name="color" value="<?php echo $editProduct ? sanitize($editProduct['color']) : ''; ?>" required minlength="1">
                    </div>

                    <div class="form-group">
                        <label>Stock</label>
                        <input type="number" min="0" name="stock" value="<?php echo $editProduct ? (int)$editProduct['stock'] : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="5"><?php echo $editProduct ? sanitize($editProduct['description']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block"><?php echo $editProduct ? 'Update Product' : 'Add Product'; ?></button>
                </form>
            </div>

            <div class="glass table-card">
                <h3>All Products</h3>

                <div class="product-admin-list">
                    <?php foreach ($products as $product): ?>
                        <div class="product-admin-item">
                            <div class="product-admin-left">
                                <img src="<?php echo sanitize($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>">
                                <div>
                                    <h4><?php echo sanitize($product['name']); ?></h4>
                                    <p class="muted">Rs.<?php echo number_format($product['price'], 2); ?> • <?php echo sanitize($product['storage']); ?> • <?php echo sanitize($product['color']); ?> • Stock: <?php echo (int)$product['stock']; ?></p>
                                </div>
                            </div>

                            <div class="product-admin-actions">
                                <a href="manage_products.php?edit=<?php echo (int)$product['id']; ?>" class="btn btn-outline btn-sm">Edit</a>

                                <form method="POST" onsubmit="return confirm('Delete this product?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$product['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($products)): ?>
                        <p class="muted">No products available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>