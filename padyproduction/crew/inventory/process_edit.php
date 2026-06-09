<?php

/**
 * crew/inventory/process_edit.php — VERSI DIPERBAIKI
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
    header("Location: index.php");
    exit();
}

$id               = (int) ($_POST['id']             ?? 0);
$category_id      = (int) ($_POST['category_id']    ?? 0);
$item_name        = sanitize($conn, $_POST['item_name']        ?? '');
$quantity         = max(0, (int) ($_POST['quantity']           ?? 0));
$unit             = sanitize($conn, $_POST['unit']             ?? 'Unit');
$condition_status = sanitize($conn, $_POST['condition_status'] ?? 'Baik');
$notes            = sanitize($conn, $_POST['notes']            ?? '');

$errors = [];
if ($id <= 0)             $errors[] = "ID tidak valid.";
if ($category_id <= 0)    $errors[] = "Kategori wajib dipilih.";
if (empty($item_name))    $errors[] = "Nama inventaris wajib diisi.";
if ($quantity < 0)        $errors[] = "Jumlah tidak boleh negatif.";

// Pastikan quantity tidak kurang dari quantity_in_use
if ($id > 0) {
    $inv = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT quantity_in_use FROM inventory WHERE id='$id' LIMIT 1")
    );
    if ($inv && $quantity < (int)$inv['quantity_in_use']) {
        $errors[] = "Jumlah tidak boleh kurang dari yang sedang dipakai ({$inv['quantity_in_use']}).";
    }
}

if (!empty($errors)) {
    $_SESSION['inv_errors'] = $errors;
    header("Location: edit.php?id=$id");
    exit();
}

$item_lama = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT item_name, quantity, condition_status FROM inventory WHERE id='$id' LIMIT 1")
);

mysqli_query($conn, "
    UPDATE inventory SET
        category_id      = '$category_id',
        item_name        = '$item_name',
        quantity         = '$quantity',
        unit             = '$unit',
        condition_status = '$condition_status',
        notes            = '$notes'
    WHERE id = '$id'
");

// Log perubahan
$desc = "Inventaris diperbarui.";
if ($item_lama) {
    $changes = [];
    if ($item_lama['item_name']        !== $item_name)        $changes[] = "Nama: {$item_lama['item_name']} → $item_name";
    if ((int)$item_lama['quantity']    !== $quantity)          $changes[] = "Jumlah: {$item_lama['quantity']} → $quantity";
    if ($item_lama['condition_status'] !== $condition_status)  $changes[] = "Kondisi: {$item_lama['condition_status']} → $condition_status";
    if (!empty($changes)) $desc = implode('; ', $changes);
}

logInventory($conn, $id, (int)$_SESSION['id'], 'Edit', $desc);

// Notifikasi owner jika kondisi memburuk
$kondisi_urut = ['Sangat Baik' => 5, 'Baik' => 4, 'Cukup' => 3, 'Kurang Baik' => 2, 'Buruk' => 1];
if ($item_lama && ($kondisi_urut[$condition_status] ?? 3) < ($kondisi_urut[$item_lama['condition_status']] ?? 3)) {
    $owners = mysqli_query($conn, "SELECT id FROM users WHERE role='owner'");
    while ($o = mysqli_fetch_assoc($owners)) {
        insertNotification($conn, (int)$o['id'],
            "⚠️ Kondisi Inventaris Menurun",
            "Kondisi '{$item_name}' berubah dari '{$item_lama['condition_status']}' menjadi '{$condition_status}'."
        );
    }
}

redirectWithMessage("index.php", "success", "Inventaris berhasil diperbarui!");