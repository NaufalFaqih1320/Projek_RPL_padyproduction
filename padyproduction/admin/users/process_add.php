<?php

session_start();
require_once("../../config/database.php");
require_once("../../config/auth.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php"); exit();
}

$name       = sanitize($conn, $_POST['name']);
$username   = sanitize($conn, $_POST['username']);
$email      = sanitize($conn, $_POST['email']);
$password   = trim($_POST['password']);
$no_telepon = sanitize($conn, $_POST['no_telepon'] ?? '');
$role       = sanitize($conn, $_POST['role']);

$errors = [];
if (empty($name))     $errors[] = "Nama wajib diisi.";
if (empty($username)) $errors[] = "Username wajib diisi.";
if (empty($email))    $errors[] = "Email wajib diisi.";
if (empty($password)) $errors[] = "Password wajib diisi.";
if (!in_array($role, ['admin','owner','crew','client'])) $errors[] = "Role tidak valid.";

// Cek duplikasi
if (empty($errors)) {
    $cek = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM users WHERE username='$username' OR email='$email' LIMIT 1"));
    if ($cek) $errors[] = "Username atau email sudah digunakan.";
}

if (!empty($errors)) {
    $_SESSION['user_errors'] = $errors;
    $_SESSION['user_old']    = $_POST;
    header("Location: add.php"); exit();
}

mysqli_query($conn, "INSERT INTO users (name,username,email,password,no_telepon,role)
    VALUES ('$name','$username','$email','$password','$no_telepon','$role')");
$user_id = mysqli_insert_id($conn);

// Buat data client jika role client
if ($role === 'client') {
    mysqli_query($conn, "INSERT INTO clients (user_id) VALUES ('$user_id')");
}

redirectWithMessage("index.php", "success", "Pengguna <strong>$name</strong> berhasil ditambahkan.");
