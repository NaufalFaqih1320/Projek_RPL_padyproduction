<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");

$query = mysqli_query($conn,"
SELECT
    ic.id,
    ic.category_name,

    COUNT(i.id) as total_item,

    COALESCE(SUM(i.quantity),0) as total_stock

FROM inventory_categories ic

LEFT JOIN inventory i
ON ic.id = i.category_id

GROUP BY ic.id
");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Inventaris</title>
</head>
<body>

<h1>Inventaris</h1>

<a href="add.php">
Tambah Inventaris
</a>

<hr>

<?php while($row = mysqli_fetch_assoc($query)): ?>

<div style="
border:1px solid #ccc;
padding:20px;
margin-bottom:15px;
">

<h2><?= $row['category_name']; ?></h2>

<p>
Total Jenis Barang :
<?= $row['total_item']; ?>
</p>

<p>
Total Stock :
<?= $row['total_stock']; ?>
</p>

<a href="detail.php?id=<?= $row['id']; ?>">
Detail
</a>

</div>

<?php endwhile; ?>

</body>
</html>