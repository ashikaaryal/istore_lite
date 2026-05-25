<?php
session_start();

require_once 'includes/auth.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = sanitize(trim($_POST['name'] ?? ''));
    $emailRaw = trim($_POST['email'] ?? '');
    $email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (!$name || !$emailRaw || !$password || !$confirmPassword) {

        $error = "All fields are required.";

    } elseif (!preg_match('/^[A-Za-z ]+$/', $name)) {

        $error = "Name can contain only letters and spaces.";

    } elseif (!$email) {

        $error = "Please enter a valid email address.";

    } elseif (!preg_match('/^.{6,}$/', $password)) {

        $error = "Password must be at least 6 characters.";

    } elseif ($password !== $confirmPassword) {

        $error = "Passwords do not match.";

    } else {

        // Check existing email
        $checkSql = "SELECT id FROM users WHERE email = ? LIMIT 1";
        $checkStmt = mysqli_prepare($conn, $checkSql);

        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);

        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_fetch_assoc($checkResult)) {

            $error = "Email already exists.";

        } else {

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $insertSql = "INSERT INTO users (name, email, password, role) 
                          VALUES (?, ?, ?, 'user')";

            $insertStmt = mysqli_prepare($conn, $insertSql);

            mysqli_stmt_bind_param(
                $insertStmt,
                "sss",
                $name,
                $email,
                $hashedPassword
            );

            if (mysqli_stmt_execute($insertStmt)) {

                // Success message
                $_SESSION['success'] = "Registration successful. Please login.";

                // Auto redirect to login page
                header("Location: login.php");
                exit();

            } else {

                $error = "Something went wrong. Please try again.";
            }

            mysqli_stmt_close($insertStmt);
        }

        mysqli_stmt_close($checkStmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - iStore Lite</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="page-bg"></div>

<div class="auth-wrapper">

    <div class="auth-card glass">

        <a href="index.php" class="logo auth-logo">
             iStore Lite
        </a>

        <h2>Create Account</h2>

        <p class="muted center-text">
            Join iStore Lite today.
        </p>

        <?php if ($error): ?>
            <div class="alert error">
                <?php echo sanitize($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label for="name">Full Name</label>

                <input
                    type="text"
                    name="name"
                    id="name"
                    required
                    minlength="2"
                    maxlength="50"
                    pattern="[A-Za-z ]+"
                    title="Name can contain only letters and spaces."
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>

                <input
                    type="email"
                    name="email"
                    id="email"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                    minlength="6"
                    pattern=".{6,}"
                    title="Password must be at least 6 characters."
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>

                <input
                    type="password"
                    name="confirm_password"
                    id="confirm_password"
                    required
                    minlength="6"
                    pattern=".{6,}"
                    title="Password must be at least 6 characters."
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Register
            </button>

        </form>

    </div>

</div>

</body>
</html>