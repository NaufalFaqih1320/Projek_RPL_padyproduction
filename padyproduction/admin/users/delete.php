<?php

session_start();
require_once("../../config/database.php");
require_once("../../config/auth.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php"); exit();
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0 || $id == $_SESSION['id']) {
    redirectWithMessage("index.php", "danger", "Tidak dapat menghapus akun ini.");
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM users WHERE id='$id' LIMIT 1"));
if (!$user) { redirectWithMessage("index.php", "danger", "Pengguna tidak ditemukan."); }

mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
redirectWithMessage("index.php", "success", "Pengguna <strong>{$user['name']}</strong> berhasil dihapus.");
