<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$id         = (int) ($_POST['id'] ?? 0);
$username   = sanitize($conn, $_POST['username']   ?? '');
$name       = sanitize($conn, $_POST['name']       ?? '');
$email      = sanitize($conn, $_POST['email']      ?? '');
$no_telepon = sanitize($conn, $_POST['no_telepon'] ?? '');
$password   = $_POST['password']  ?? '';
$password2  = $_POST['password2'] ?? '';
$role       = sanitize($conn, $_POST['role'] ?? 'client');

$errors = [];
if ($id <= 0)        $errors[] = "ID pengguna tidak valid.";
if (empty($username)) $errors[] = "Username wajib diisi.";
if (empty($name))    $errors[] = "Nama wajib diisi.";
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email tidak valid.";

$valid_roles = ['admin', 'owner', 'crew', 'client'];
if (!in_array($role, $valid_roles)) $errors[] = "Role tidak valid.";

// Validasi password hanya jika diisi
if (!empty($password)) {
    if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter.";
    if ($password !== $password2) $errors[] = "Konfirmasi password tidak cocok.";
}

// Cek duplikat (kecuali milik sendiri)
if (empty($errors)) {
    $cek = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM users WHERE username='$username' AND id!='$id' LIMIT 1"
    ));
    if ($cek) $errors[] = "Username sudah digunakan.";

    $cek2 = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM users WHERE email='$email' AND id!='$id' LIMIT 1"
    ));
    if ($cek2) $errors[] = "Email sudah terdaftar.";
}

if (!empty($errors)) {
    $_SESSION['user_errors'] = $errors;
    $_SESSION['user_old']    = $_POST;
    header("Location: edit.php?id=$id");
    exit();
}

// Cegah admin hapus role diri sendiri
if ($id == (int)$_SESSION['id'] && $role !== 'admin') {
    $_SESSION['user_errors'] = ["Anda tidak bisa mengubah role akun sendiri."];
    header("Location: edit.php?id=$id");
    exit();
}

if (!empty($password)) {
    $hashed     = password_hash($password, PASSWORD_BCRYPT);
    $hashed_esc = mysqli_real_escape_string($conn, $hashed);
    mysqli_query($conn,
        "UPDATE users SET username='$username', name='$name', email='$email',
         no_telepon='$no_telepon', password='$hashed_esc', role='$role'
         WHERE id='$id'"
    );
} else {
    mysqli_query($conn,
        "UPDATE users SET username='$username', name='$name', email='$email',
         no_telepon='$no_telepon', role='$role'
         WHERE id='$id'"
    );
}

redirectWithMessage("index.php", "success", "Data pengguna <strong>$name</strong> berhasil diperbarui.");
