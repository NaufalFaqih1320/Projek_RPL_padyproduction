<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");

$categories = mysqli_query(
    $conn,
    "SELECT * FROM inventory_categories"
);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Inventaris</title>
</head>
<body>

<h1>Tambah Inventaris</h1>

<form action="process_add.php" method="POST">

<label>Kategori</label><br>

<select name="category_id" required>

<?php while($category = mysqli_fetch_assoc($categories)): ?>

<option value="<?= $category['id']; ?>">
    <?= $category['category_name']; ?>
</option>

<?php endwhile; ?>

</select>

<br><br>

<label>Nama Inventaris</label><br>

<input
type="text"
name="item_name"
required
>

<br><br>

<label>Jumlah</label><br>

<input
type="number"
name="quantity"
required
min="1"
>

<br><br>

<label>Satuan</label><br>

<input
type="text"
name="unit"
value="Unit"
required
>

<br><br>

<label>Kondisi</label><br>

<select name="condition_status">

<option value="Sangat Baik">
Sangat Baik
</option>

<option value="Baik">
Baik
</option>

<option value="Cukup">
Cukup
</option>

<option value="Kurang Baik">
Kurang Baik
</option>

<option value="Buruk">
Buruk
</option>

</select>

<br><br>

<button type="submit">
Simpan
</button>

</form>

</body>
</html>