<?php
// crew/inventory/process_edit.php
require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if (!in_array($_SESSION['role'], ['admin', 'crew'])) {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$id               = (int) ($_POST['id']               ?? 0);
$category_id      = (int) ($_POST['category_id']      ?? 0);
$item_name        = sanitize($conn, $_POST['item_name']        ?? '');
$quantity         = max(0, (int) ($_POST['quantity']           ?? 0));
$unit             = sanitize($conn, $_POST['unit']             ?? 'Unit');
$condition_status = sanitize($conn, $_POST['condition_status'] ?? 'Baik');
$keterangan       = sanitize($conn, $_POST['keterangan']       ?? '');

$errors = [];
if ($id <= 0)          $errors[] = "ID tidak valid.";
if ($category_id <= 0) $errors[] = "Pilih kategori.";
if (empty($item_name)) $errors[] = "Nama item wajib diisi.";

$valid_conditions = ['Sangat Baik', 'Baik', 'Cukup', 'Kurang Baik', 'Buruk'];
if (!in_array($condition_status, $valid_conditions)) $errors[] = "Kondisi tidak valid.";

if (!empty($errors)) {
    $_SESSION['inv_errors'] = $errors;
    $_SESSION['inv_old']    = $_POST;
    header("Location: edit.php?id=$id");
    exit();
}

$item = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM inventory WHERE id='$id' LIMIT 1"
));
if (!$item) redirectWithMessage("index.php", "danger", "Item tidak ditemukan.");

if ($quantity < (int)$item['quantity_in_use']) {
    $_SESSION['inv_errors'] = [
        "Jumlah baru ($quantity) tidak boleh lebih kecil dari yang sedang dipakai ({$item['quantity_in_use']})."
    ];
    header("Location: edit.php?id=$id");
    exit();
}

mysqli_query($conn,
    "UPDATE inventory
     SET category_id='$category_id', item_name='$item_name', quantity='$quantity',
         unit='$unit', condition_status='$condition_status', keterangan='$keterangan'
     WHERE id='$id'"
);
logInventory($conn, $id, (int)$_SESSION['id'], 'Edit', "Item '$item_name' diperbarui.");

redirectWithMessage("index.php", "success", "Item <strong>$item_name</strong> berhasil diperbarui.");
