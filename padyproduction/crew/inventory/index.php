<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");

$result = mysqli_query($conn, "
SELECT
    inventory.*,
    inventory_categories.category_name
FROM inventory
JOIN inventory_categories
ON inventory.category_id = inventory_categories.id
ORDER BY inventory.id DESC
");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Inventaris</title>
</head>
<body>

<h1>Daftar Inventaris</h1>

<a href="add.php">
    Tambah Inventaris
</a>

<hr>

<?php while($row = mysqli_fetch_assoc($result)): ?>

<div style="
border:1px solid #ccc;
padding:15px;
margin-bottom:10px;
">

<h3><?= $row['item_name']; ?></h3>

<p>Kategori : <?= $row['category_name']; ?></p>

<p>Jumlah : <?= $row['quantity']; ?> <?= $row['unit']; ?></p>

<p>Kondisi : <?= $row['condition_status']; ?></p>

<a href="edit.php?id=<?= $row['id']; ?>">
    Edit
</a>

|

<a href="delete.php?id=<?= $row['id']; ?>">
    Hapus
</a>

</div>

<?php endwhile; ?>

</body>
</html>