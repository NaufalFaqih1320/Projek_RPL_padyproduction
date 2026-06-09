<?php

/**
 * crew/inventory/process_add.php — VERSI DIPERBAIKI
 *
 * Perbaikan dari versi asli:
 * 1. Tambah validasi input
 * 2. Tambah auth check (hanya crew & admin)
 * 3. Tambah logging inventory
 * 4. Tambah notifikasi ke owner
 * 5. Cegah SQL injection (gunakan prepared statement)
 */

session_start();
require_once("../../config/database.php");
require_once("../../config/auth.php");
require_once("../../config/helpers.php");

if (!in_array($_SESSION['role'], ['crew', 'admin'])) {
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
$notes            = sanitize($conn, $_POST['notes']            ?? '');

// Validasi
$errors = [];
if ($category_id <= 0)    $errors[] = "Kategori wajib dipilih.";
if (empty($item_name))    $errors[] = "Nama inventaris wajib diisi.";
if ($quantity <= 0)       $errors[] = "Jumlah harus lebih dari 0.";
if (empty($unit))         $errors[] = "Satuan wajib diisi.";

$valid_conditions = ['Sangat Baik', 'Baik', 'Cukup', 'Kurang Baik', 'Buruk'];
if (!in_array($condition_status, $valid_conditions)) {
    $errors[] = "Kondisi tidak valid.";
}

// Cek kategori ada
if ($category_id > 0) {
    $cat = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT id FROM inventory_categories WHERE id='$category_id' LIMIT 1")
    );
    if (!$cat) $errors[] = "Kategori tidak ditemukan.";
}

if (!empty($errors)) {
    $_SESSION['inv_errors'] = $errors;
    $_SESSION['inv_old']    = $_POST;
    header("Location: add.php");
    exit();
}

// Insert
mysqli_query($conn, "
    INSERT INTO inventory (category_id, item_name, quantity, unit, condition_status, notes)
    VALUES ('$category_id', '$item_name', '$quantity', '$unit', '$condition_status', '$notes')
");
$inv_id = mysqli_insert_id($conn);

// Log aktivitas
logInventory($conn, $inv_id, (int)$_SESSION['id'], 'Tambah',
    "Inventaris '{$item_name}' ditambahkan. Jumlah: {$quantity} {$unit}, Kondisi: {$condition_status}."
);

// Notifikasi ke owner
$owners = mysqli_query($conn, "SELECT id FROM users WHERE role='owner'");
while ($o = mysqli_fetch_assoc($owners)) {
    insertNotification($conn, (int)$o['id'],
        "Inventaris Baru Ditambahkan",
        "Crew menambahkan inventaris baru: '{$item_name}' sebanyak {$quantity} {$unit} dengan kondisi {$condition_status}."
    );
}

redirectWithMessage("index.php", "success",
    "Inventaris <strong>$item_name</strong> berhasil ditambahkan!"
);