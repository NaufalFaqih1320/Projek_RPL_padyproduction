<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");

$category_id = $_GET['id'];

$category = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT * FROM inventory_categories
        WHERE id='$category_id'"
    )
);

$items = mysqli_query(
    $conn,
    "SELECT *
    FROM inventory
    WHERE category_id='$category_id'"
);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Inventaris</title>
</head>
<body>

<h1>
Kategori :
<?= $category['category_name']; ?>
</h1>

<a href="index.php">
Kembali
</a>

<hr>

<?php while($item = mysqli_fetch_assoc($items)): ?>

<div style="
border:1px solid #ccc;
padding:15px;
margin-bottom:10px;
">

<h3>
<?= $item['item_name']; ?>
</h3>

<p>
Jumlah :
<?= $item['quantity']; ?>
<?= $item['unit']; ?>
</p>

<p>
Kondisi :
<?= $item['condition_status']; ?>
</p>

<a href="edit.php?id=<?= $item['id']; ?>">
Edit
</a>

|

<a href="delete.php?id=<?= $item['id']; ?>" onclick="return confirm('Yakin ingin menghapus inventaris ini?')">
Hapus
</a>

</div>

<?php endwhile; ?>

</body>
</html>