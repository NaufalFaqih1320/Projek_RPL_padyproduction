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
    redirectWithMessage("index.php", "danger", "ID tidak valid.");
}

// Tidak boleh hapus akun sendiri
if ($id === (int)$_SESSION['id']) {
    redirectWithMessage("index.php", "danger", "Tidak bisa menghapus akun sendiri.");
}

$user = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id, name, role FROM users WHERE id='$id' LIMIT 1"
));

if (!$user) {
    redirectWithMessage("index.php", "danger", "Pengguna tidak ditemukan.");
}

// Tidak boleh hapus admin terakhir
if ($user['role'] === 'admin') {
    $jumlah_admin = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS n FROM users WHERE role='admin'"
    ))['n'];
    if ($jumlah_admin <= 1) {
        redirectWithMessage("index.php", "danger", "Tidak bisa menghapus satu-satunya admin.");
    }
}

mysqli_query($conn, "DELETE FROM users WHERE id='$id'");

redirectWithMessage("index.php", "success", "Pengguna <strong>{$user['name']}</strong> berhasil dihapus.");
