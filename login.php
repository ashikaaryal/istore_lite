<?php
require_once 'includes/auth.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit;
}

$error = "";
$rememberMe = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);

    if (!$email || !$password) {
        $error = "Please enter a valid email and password.";
    } else {
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            if ($rememberMe) {
                setRememberMeCookie((int)$user['id'], $user['email']);
            } else {
                clearRememberMeCookie();
            }

            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - iStore Lite</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="landing-body">
<div class="landing-bg"></div>

<div class="auth-wrapper">
    <div class="auth-card glass">
        <a href="index.php" class="brand auth-logo">iStore <span>lite</span></a>
        <h2>Welcome Back</h2>
        <p class="muted center-text">Sign in to continue.</p>

        <?php if ($error): ?>
            <div class="alert error"><?php echo sanitize($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required minlength="6">
            </div>

            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="remember_me" id="remember_me" <?php echo $rememberMe ? 'checked' : ''; ?>> Remember me
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>

        <p class="switch-link">Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</div>
</body>
</html>