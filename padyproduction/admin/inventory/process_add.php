<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

// Hanya admin dan crew yang boleh tambah inventaris
if (!in_array($_SESSION['role'], ['admin', 'crew'])) {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: add.php");
    exit();
}

// Ambil & sanitasi input
$category_id      = (int) ($_POST['category_id']      ?? 0);
$item_name        = sanitize($conn, $_POST['item_name']        ?? '');
$quantity         = max(0, (int) ($_POST['quantity']           ?? 0));
$unit             = sanitize($conn, $_POST['unit']             ?? 'Unit');
$condition_status = sanitize($conn, $_POST['condition_status'] ?? 'Baik');
$keterangan       = sanitize($conn, $_POST['keterangan']       ?? '');

// Validasi
$errors = [];
if ($category_id <= 0) $errors[] = "Pilih kategori terlebih dahulu.";
if (empty($item_name)) $errors[] = "Nama item wajib diisi.";
if ($quantity < 0)     $errors[] = "Jumlah tidak boleh negatif.";

// Validasi nilai enum condition_status
$valid_conditions = ['Sangat Baik', 'Baik', 'Cukup', 'Kurang Baik', 'Buruk'];
if (!in_array($condition_status, $valid_conditions)) {
    $errors[] = "Kondisi tidak valid.";
}

if (!empty($errors)) {
    $_SESSION['inv_errors'] = $errors;
    $_SESSION['inv_old']    = $_POST;
    header("Location: add.php");
    exit();
}

// Cek kategori ada
$cek = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM inventory_categories WHERE id='$category_id' LIMIT 1"
));
if (!$cek) {
    $_SESSION['inv_errors'] = ["Kategori tidak ditemukan."];
    header("Location: add.php");
    exit();
}

// Insert
mysqli_query($conn,
    "INSERT INTO inventory (category_id, item_name, quantity, quantity_in_use, unit, condition_status, keterangan)
     VALUES ('$category_id','$item_name','$quantity', 0,'$unit','$condition_status','$keterangan')"
);
$new_id = mysqli_insert_id($conn);

// Log
logInventory($conn, $new_id, (int)$_SESSION['id'], 'Tambah', "Item '$item_name' ditambahkan.");

redirectWithMessage("index.php", "success", "Item <strong>$item_name</strong> berhasil ditambahkan.");
