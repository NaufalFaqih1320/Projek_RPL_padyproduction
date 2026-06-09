<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");

$id = $_GET['id'];

$item = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT * FROM inventory WHERE id='$id'"
    )
);

$categories = mysqli_query(
    $conn,
    "SELECT * FROM inventory_categories"
);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Inventaris</title>
</head>
<body>

<h1>Edit Inventaris</h1>

<form action="process_edit.php" method="POST">

<input
type="hidden"
name="id"
value="<?= $item['id']; ?>"
>

<label>Kategori</label><br>

<select name="category_id">

<?php while($category = mysqli_fetch_assoc($categories)): ?>

<option
value="<?= $category['id']; ?>"
<?= ($category['id'] == $item['category_id']) ? 'selected' : ''; ?>
>
<?= $category['category_name']; ?>
</option>

<?php endwhile; ?>

</select>

<br><br>

<label>Nama Inventaris</label><br>

<input
type="text"
name="item_name"
value="<?= $item['item_name']; ?>"
required
>

<br><br>

<label>Jumlah</label><br>

<input
type="number"
name="quantity"
value="<?= $item['quantity']; ?>"
required
>

<br><br>

<label>Satuan</label><br>

<input
type="text"
name="unit"
value="<?= $item['unit']; ?>"
required
>

<br><br>

<label>Kondisi</label><br>

<select name="condition_status">

<?php

$conditions = [
    "Sangat Baik",
    "Baik",
    "Cukup",
    "Kurang Baik",
    "Buruk"
];

foreach($conditions as $condition):

?>

<option
value="<?= $condition; ?>"
<?= ($condition == $item['condition_status']) ? 'selected' : ''; ?>
>
<?= $condition; ?>
</option>

<?php endforeach; ?>

</select>

<br><br>

<button type="submit">
Update
</button>

</form>

</body>
</html>