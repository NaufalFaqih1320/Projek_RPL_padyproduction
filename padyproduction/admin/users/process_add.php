<?php

require_once("../../config/auth.php");
require_once("../../config/database.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: add.php");
    exit();
}

$username    = sanitize($conn, $_POST['username']    ?? '');
$name        = sanitize($conn, $_POST['name']        ?? '');
$email       = sanitize($conn, $_POST['email']       ?? '');
$no_telepon  = sanitize($conn, $_POST['no_telepon']  ?? '');
$password    = $_POST['password']    ?? '';
$password2   = $_POST['password2']   ?? '';
$role        = sanitize($conn, $_POST['role']        ?? 'client');

$errors = [];
if (empty($username))   $errors[] = "Username wajib diisi.";
if (empty($name))       $errors[] = "Nama wajib diisi.";
if (empty($email))      $errors[] = "Email wajib diisi.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid.";
if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter.";
if ($password !== $password2) $errors[] = "Konfirmasi password tidak cocok.";

$valid_roles = ['admin', 'owner', 'crew', 'client'];
if (!in_array($role, $valid_roles)) $errors[] = "Role tidak valid.";

// Cek duplikat username
if (empty($errors)) {
    $cek = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM users WHERE username='$username' LIMIT 1"
    ));
    if ($cek) $errors[] = "Username sudah digunakan.";
}

// Cek duplikat email
if (empty($errors)) {
    $cek = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM users WHERE email='$email' LIMIT 1"
    ));
    if ($cek) $errors[] = "Email sudah terdaftar.";
}

if (!empty($errors)) {
    $_SESSION['user_errors'] = $errors;
    $_SESSION['user_old']    = $_POST;
    header("Location: add.php");
    exit();
}

$hashed = password_hash($password, PASSWORD_BCRYPT);
$hashed_esc = mysqli_real_escape_string($conn, $hashed);

mysqli_query($conn,
    "INSERT INTO users (username, name, email, no_telepon, password, role)
     VALUES ('$username','$name','$email','$no_telepon','$hashed_esc','$role')"
);

redirectWithMessage("index.php", "success", "Pengguna <strong>$name</strong> berhasil ditambahkan.");
