<?php
// crew/inventory/process_add.php — identik dengan admin, hanya akses crew
require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if (!in_array($_SESSION['role'], ['admin', 'crew'])) {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: add.php");
    exit();
}

$category_id      = (int) ($_POST['category_id']      ?? 0);
$item_name        = sanitize($conn, $_POST['item_name']        ?? '');
$quantity         = max(0, (int) ($_POST['quantity']           ?? 0));
$unit             = sanitize($conn, $_POST['unit']             ?? 'Unit');
$condition_status = sanitize($conn, $_POST['condition_status'] ?? 'Baik');
$keterangan       = sanitize($conn, $_POST['keterangan']       ?? '');

$errors = [];
if ($category_id <= 0) $errors[] = "Pilih kategori.";
if (empty($item_name)) $errors[] = "Nama item wajib diisi.";

$valid_conditions = ['Sangat Baik', 'Baik', 'Cukup', 'Kurang Baik', 'Buruk'];
if (!in_array($condition_status, $valid_conditions)) $errors[] = "Kondisi tidak valid.";

if (!empty($errors)) {
    $_SESSION['inv_errors'] = $errors;
    $_SESSION['inv_old']    = $_POST;
    header("Location: add.php");
    exit();
}

mysqli_query($conn,
    "INSERT INTO inventory (category_id, item_name, quantity, quantity_in_use, unit, condition_status, keterangan)
     VALUES ('$category_id','$item_name','$quantity', 0,'$unit','$condition_status','$keterangan')"
);
$new_id = mysqli_insert_id($conn);
logInventory($conn, $new_id, (int)$_SESSION['id'], 'Tambah', "Item '$item_name' ditambahkan oleh crew.");

redirectWithMessage("index.php", "success", "Item <strong>$item_name</strong> berhasil ditambahkan.");
