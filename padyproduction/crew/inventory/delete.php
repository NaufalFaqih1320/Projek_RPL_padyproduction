<?php

/**
 * crew/inventory/delete.php — VERSI DIPERBAIKI
 * Cegah hapus inventaris yang sedang dipakai
 */

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");


if (!in_array($_SESSION['role'], ['crew', 'admin'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) redirectWithMessage("index.php", "danger", "ID tidak valid.");

$item = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM inventory WHERE id='$id' LIMIT 1")
);
if (!$item) redirectWithMessage("index.php", "danger", "Item tidak ditemukan.");

// Cek apakah sedang dipakai di booking aktif
if ((int)$item['quantity_in_use'] > 0) {
    redirectWithMessage("index.php", "danger",
        "Inventaris <strong>{$item['item_name']}</strong> tidak dapat dihapus karena sedang digunakan ({$item['quantity_in_use']} unit)."
    );
}

logInventory($conn, $id, (int)$_SESSION['id'], 'Hapus',
    "Inventaris '{$item['item_name']}' dihapus. Jumlah: {$item['quantity']} {$item['unit']}."
);

mysqli_query($conn, "DELETE FROM inventory WHERE id='$id'");

$owners = mysqli_query($conn, "SELECT id FROM users WHERE role='owner'");
while ($o = mysqli_fetch_assoc($owners)) {
    insertNotification($conn, (int)$o['id'],
        "Inventaris Dihapus",
        "Crew menghapus inventaris: '{$item['item_name']}' ({$item['quantity']} {$item['unit']})."
    );
}

redirectWithMessage("index.php", "success",
    "Inventaris <strong>{$item['item_name']}</strong> berhasil dihapus."
);