<?php
require_once 'includes/auth.php';
requireLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <div class="glass" style="padding:40px; margin-top:50px; text-align:center;">

        <h2>Payment Failed</h2>

        <p>
            Your eSewa payment could not be completed.
        </p>

        <a href="checkout.php" class="btn btn-primary">
            Try Again
        </a>

    </div>

</div>

</body>
</html>