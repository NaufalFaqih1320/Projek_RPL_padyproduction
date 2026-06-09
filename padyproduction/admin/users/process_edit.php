<?php

/**
 * admin/users/process_edit.php — VERSI DIPERBAIKI
 * Password di-hash jika diubah
 */

session_start();
require_once("../../config/database.php");
require_once("../../config/auth.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$id         = (int) ($_POST['id']          ?? 0);
$name       = sanitize($conn, $_POST['name']       ?? '');
$username   = sanitize($conn, $_POST['username']   ?? '');
$email      = sanitize($conn, $_POST['email']      ?? '');
$password   = trim($_POST['password']              ?? '');
$no_telepon = sanitize($conn, $_POST['no_telepon'] ?? '');
$role       = sanitize($conn, $_POST['role']       ?? '');

$errors = [];
if ($id <= 0)          $errors[] = "ID tidak valid.";
if (empty($name))      $errors[] = "Nama wajib diisi.";
if (empty($username))  $errors[] = "Username wajib diisi.";
if (empty($email))     $errors[] = "Email wajib diisi.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid.";
if (!empty($password) && strlen($password) < 6) $errors[] = "Password baru minimal 6 karakter.";
if (!in_array($role, ['admin','owner','crew','client','user'])) $errors[] = "Role tidak valid.";

// Cegah admin menghapus role sendiri
if ($id === (int)$_SESSION['id'] && $role !== 'admin') {
    $errors[] = "Anda tidak bisa mengubah role akun Anda sendiri.";
}

if (empty($errors)) {
    $cek = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM users WHERE (username='$username' OR email='$email') AND id!='$id' LIMIT 1"
    ));
    if ($cek) $errors[] = "Username atau email sudah digunakan pengguna lain.";
}

if (!empty($errors)) {
    $_SESSION['user_errors'] = $errors;
    header("Location: edit.php?id=$id");
    exit();
}

$pass_clause = '';
if (!empty($password)) {
    $hashed     = password_hash($password, PASSWORD_BCRYPT);
    $hashed_esc = mysqli_real_escape_string($conn, $hashed);
    $pass_clause = ", password='$hashed_esc'";
}

mysqli_query($conn, "
    UPDATE users SET
        name       = '$name',
        username   = '$username',
        email      = '$email',
        no_telepon = '$no_telepon',
        role       = '$role'
        $pass_clause
    WHERE id = '$id'
");

redirectWithMessage("index.php", "success", "Data pengguna berhasil diperbarui.");