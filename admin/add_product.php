<!-- <?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $storage = $_POST['storage'];
    $color = $_POST['color'];
    $stock = $_POST['stock'];

    $image = $_FILES['image']['name'];
    move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image);

    $conn->query("INSERT INTO products (name,price,storage,color,stock,image)
    VALUES ('$name','$price','$storage','$color','$stock','$image')");

    echo "Product Added!";
}
?>

<form method="POST" enctype="multipart/form-data">
    <input name="name" placeholder="iPhone Name">
    <input name="price" placeholder="Price">
    <input name="storage" placeholder="Storage">
    <input name="color" placeholder="Color">
    <input name="stock" placeholder="Stock">
    <input type="file" name="image">
    <button name="submit">Add Product</button>
</form> -->