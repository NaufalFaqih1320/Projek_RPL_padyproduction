<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    redirectWithMessage("index.php", "danger", "ID item tidak valid.");
}

// Cek item ada
$item = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id, item_name, quantity_in_use FROM inventory WHERE id='$id' LIMIT 1"
));

if (!$item) {
    redirectWithMessage("index.php", "danger", "Item tidak ditemukan.");
}

// Cegah hapus item yang sedang dipakai di booking
if ((int)$item['quantity_in_use'] > 0) {
    redirectWithMessage("index.php", "danger",
        "Item <strong>{$item['item_name']}</strong> tidak bisa dihapus karena sedang dipakai di booking aktif.");
}

$item_name = $item['item_name'];

mysqli_query($conn, "DELETE FROM inventory WHERE id='$id'");

redirectWithMessage("index.php", "success", "Item <strong>$item_name</strong> berhasil dihapus.");
