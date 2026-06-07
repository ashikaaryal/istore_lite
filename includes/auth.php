<?php

define('REMEMBER_ME_COOKIE', 'remember_me');

define('AUTH_SECRET_KEY', 'replace_this_with_a_strong_secret_key');

$secureCookie = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Restore remembered login
if (!isLoggedIn()) {
    restoreRememberedSession($conn);
}

/*
|--------------------------------------------------------------------------
| AUTH FUNCTIONS
|--------------------------------------------------------------------------
*/

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['role']) &&
           $_SESSION['role'] === 'admin';
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function requireSiteLogin()
{
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function requireAdmin()
{
    if (!isLoggedIn() || !isAdmin()) {
        header("Location: login.php");
        exit;
    }
}

function getCurrentUserName()
{
    return $_SESSION['user_name'] ?? 'Guest';
}

/*
|--------------------------------------------------------------------------
| REMEMBER ME FUNCTIONS
|--------------------------------------------------------------------------
*/

function generateRememberMeToken($userId, $email)
{
    return hash_hmac(
        'sha256',
        $userId . '|' . $email,
        AUTH_SECRET_KEY
    );
}

function fetchUserById($conn, $id)
{
    $sql = "
        SELECT id, name, email, role
        FROM users
        WHERE id = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $user;
}

function setRememberMeCookie($userId, $email)
{
    $token = $userId . '|' .
             generateRememberMeToken($userId, $email);

    setcookie(
        REMEMBER_ME_COOKIE,
        $token,
        [
            'expires' => time() + (60 * 60 * 24 * 30),
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

function clearRememberMeCookie()
{
    if (isset($_COOKIE[REMEMBER_ME_COOKIE])) {

        setcookie(
            REMEMBER_ME_COOKIE,
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }
}

// function restoreRememberedSession($conn)
// {
//     if (empty($_COOKIE[REMEMBER_ME_COOKIE])) {
//         return false;
//     }

//     $parts = explode('|', $_COOKIE[REMEMBER_ME_COOKIE], 2);

//     if (
//         count($parts) !== 2 ||
//         !ctype_digit($parts[0])
//     ) {

//         clearRememberMeCookie();

//         return false;
//     }

//     $userId = (int)$parts[0];

//     $token = $parts[1];

//     $user = fetchUserById($conn, $userId);

//     if (
//         !$user ||
//         !hash_equals(
//             generateRememberMeToken(
//                 $userId,
//                 $user['email']
//             ),
//             $token
//         )
//     ) {

//         clearRememberMeCookie();

//         return false;
//     }

//     $_SESSION['user_id'] = (int)$user['id'];

//     $_SESSION['user_name'] = $user['name'];

//     $_SESSION['user_email'] = $user['email'];

//     $_SESSION['role'] = $user['role'];

//     return true;
// }

function restoreRememberedSession($conn)
{
    if (empty($_COOKIE[REMEMBER_ME_COOKIE])) {
        return false;
    }

    $parts = explode('|', $_COOKIE[REMEMBER_ME_COOKIE], 2);

    if (count($parts) !== 2 || !ctype_digit($parts[0])) {
        clearRememberMeCookie();
        return false;
    }

    $userId = (int)$parts[0];
    $token = $parts[1];

    $user = fetchUserById($conn, $userId);

    if (
        !$user ||
        !hash_equals(
            generateRememberMeToken(
                $userId,
                $user['email']
            ),
            $token
        )
    ) {
        clearRememberMeCookie();
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    return true;
}
/*
|--------------------------------------------------------------------------
| SANITIZE
|--------------------------------------------------------------------------
*/

function sanitize($value)
{
    return htmlspecialchars(
        trim((string)$value),
        ENT_QUOTES,
        'UTF-8'
    );
}

/*
|--------------------------------------------------------------------------
| PRODUCT FUNCTIONS
|--------------------------------------------------------------------------
*/

function fetchAllProducts($conn)
{
    $products = [];

    $sql = "
        SELECT *
        FROM products
        ORDER BY created_at DESC
    ";

    $result = mysqli_query($conn, $sql);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }

    return $products;
}

function fetchProductById($conn, $id)
{
    $sql = "
        SELECT *
        FROM products
        WHERE id = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $product = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $product;
}

/*
|--------------------------------------------------------------------------
| CART FUNCTIONS
|--------------------------------------------------------------------------
*/

function getCartCount()
{
    $count = 0;

    if (
        !isset($_SESSION['cart']) ||
        !is_array($_SESSION['cart'])
    ) {
        return 0;
    }

    foreach ($_SESSION['cart'] as $item) {

        $count += (int)$item['quantity'];
    }

    return $count;
}

function cartGrandTotal()
{
    $total = 0;

    if (
        !isset($_SESSION['cart']) ||
        !is_array($_SESSION['cart'])
    ) {
        return 0;
    }

    foreach ($_SESSION['cart'] as $item) {

        $total += (
            (float)$item['price']
            *
            (int)$item['quantity']
        );
    }

    return $total;
}

/*
|--------------------------------------------------------------------------
| ESEWA PAYMENT SIGNATURE
|--------------------------------------------------------------------------
*/

function generateEsewaSignature(
    $totalAmount,
    $transactionUuid,
    $productCode
) {
    $secretKey = "8gBm/:&EnhH.1/q";

    $message =
        "total_amount={$totalAmount}," .
        "transaction_uuid={$transactionUuid}," .
        "product_code={$productCode}";

    $hash = hash_hmac(
        'sha256',
        $message,
        $secretKey,
        true
    );

    return base64_encode($hash);
}
?>