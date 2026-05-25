<?php
require_once 'includes/auth.php';

$products = fetchAllProducts($conn);
$userName = getCurrentUserName();
$cartCount = getCartCount();

/*
    Front page design based on the reference image:
    - top navbar
    - large hero banner
    - categories section
    - featured products section
    - footer with contact + social icons
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iStore Lite</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="landing-body">

<div class="landing-bg"></div>

<header class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="brand">iStore <span>lite</span></a>

        <nav class="topnav">
            <a href="index.php">Home</a>
            <a href="#featured">Shop</a>
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

<section class="hero-banner">
    <div class="hero-overlay">
        <div class="hero-left">
            <h1>
                Experience the <strong>Future</strong><br>
                in Your <strong>Hands</strong>
            </h1>
            <p>Explore the latest iPhones</p>
            <a href="#featured" class="hero-btn">Shop Now</a>
        </div>

        <div class="hero-right">
            <img src="https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&w=1200&q=80" alt="iPhone">
        </div>
    </div>
</section>

<section class="landing-section">
    <h2 class="section-title">Explore Categories</h2>

    <div class="category-grid">
        <a href="category.php?name=<?php echo urlencode('iPhone 15 Series'); ?>" class="category-card-link">
            <div class="category-card">
                <img src="https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&w=900&q=80" alt="iPhone 15 Series">
                <div class="category-label">iPhone 15 Series</div>
            </div>
        </a>

        <a href="category.php?name=<?php echo urlencode('iPhone 14 Series'); ?>" class="category-card-link">
            <div class="category-card">
                <img src="https://images.unsplash.com/photo-1592750475338-74b7b21085ab?auto=format&fit=crop&w=900&q=80" alt="iPhone 14 Series">
                <div class="category-label">iPhone 14 Series</div>
            </div>
        </a>

        <a href="category.php?name=<?php echo urlencode('iPhone SE'); ?>" class="category-card-link">
            <div class="category-card">
                <img src="https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&q=80" alt="iPhone SE">
                <div class="category-label">iPhone SE</div>
            </div>
        </a>
    </div>
</section>

<section class="landing-section" id="featured">
    <h2 class="section-title">Featured Products</h2>

    <div class="featured-grid">
        <?php
$featured = [];

$featuredSql = "SELECT * FROM products ORDER BY RAND() LIMIT 4";
$featuredResult = mysqli_query($conn, $featuredSql);

if ($featuredResult) {
    while ($row = mysqli_fetch_assoc($featuredResult)) {
        $featured[] = $row;
    }
}

foreach ($featured as $product):
?>
            <div class="featured-card">
                <div class="featured-image-wrap">
                    <img src="<?php echo sanitize($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>">
                </div>

                <div class="featured-info">
                    <h3><?php echo sanitize($product['name']); ?></h3>
                    <div class="featured-price">Rs.<?php echo number_format((float)$product['price'], 0); ?></div>

                    <?php if (isLoggedIn()): ?>
    <a href="product.php?id=<?php echo (int)$product['id']; ?>" 
       class="featured-btn" 
       style="display:flex;align-items:center;justify-content:center;">
        View Product
    </a>
<?php else: ?>
    <a href="login.php" 
       class="featured-btn" 
       style="display:flex;align-items:center;justify-content:center;">
        Login to Buy
    </a>
<?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($featured)): ?>
            <div class="no-products-box">
                <p>No featured products available.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<footer class="landing-footer">
    <div class="footer-col footer-left">
        <h3>Contact Us</h3>
        <p>Email: istorelite@gmail.com</p>
        <p>Phone: 97777777777</p>
    </div>

    <div class="footer-col footer-center">
        <div class="social-row">
            <a href="#" class="social-icon fb">f</a>
            <a href="#" class="social-icon tw">t</a>
            <a href="#" class="social-icon ig">◎</a>
            <a href="#" class="social-icon yt">▶</a>
        </div>
    </div>

    <div class="footer-col footer-right">
        <p>© 2024 iStore lite. All rights reserved.</p>
    </div>
</footer>

</body>
</html>