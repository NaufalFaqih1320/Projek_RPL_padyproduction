<?php

/**
 * admin/users/process_add.php — VERSI DIPERBAIKI
 *
 * Perbaikan:
 * 1. Password di-hash dengan bcrypt (password_hash)
 * 2. Validasi format email
 * 3. Validasi panjang password minimal
 * 4. Notifikasi ke owner jika user baru adalah client
 */

session_start();
require_once("../../config/database.php");
require_once("../../config/auth.php");
require_once("../../config/helpers.php");

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$name       = sanitize($conn, $_POST['name']       ?? '');
$username   = sanitize($conn, $_POST['username']   ?? '');
$email      = sanitize($conn, $_POST['email']      ?? '');
$password   = trim($_POST['password']              ?? '');
$no_telepon = sanitize($conn, $_POST['no_telepon'] ?? '');
$role       = sanitize($conn, $_POST['role']       ?? '');

$errors = [];
if (empty($name))      $errors[] = "Nama wajib diisi.";
if (empty($username))  $errors[] = "Username wajib diisi.";
if (empty($email))     $errors[] = "Email wajib diisi.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid.";
if (empty($password))  $errors[] = "Password wajib diisi.";
if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter.";
if (!in_array($role, ['admin','owner','crew','client','user'])) $errors[] = "Role tidak valid.";

// Cek duplikasi username & email
if (empty($errors)) {
    $cek = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM users WHERE username='$username' OR email='$email' LIMIT 1"
    ));
    if ($cek) $errors[] = "Username atau email sudah digunakan.";
}

if (!empty($errors)) {
    $_SESSION['user_errors'] = $errors;
    $_SESSION['user_old']    = $_POST;
    header("Location: add.php");
    exit();
}

// Hash password dengan bcrypt
$hashed_password = password_hash($password, PASSWORD_BCRYPT);
$hashed_esc      = mysqli_real_escape_string($conn, $hashed_password);

mysqli_begin_transaction($conn);
try {
    mysqli_query($conn, "
        INSERT INTO users (name, username, email, password, no_telepon, role)
        VALUES ('$name', '$username', '$email', '$hashed_esc', '$no_telepon', '$role')
    ");
    $user_id = mysqli_insert_id($conn);

    // Buat record client jika role adalah client
    if ($role === 'client') {
        mysqli_query($conn, "INSERT INTO clients (user_id) VALUES ('$user_id')");
    }

    // Notifikasi ke owner jika client baru
    if ($role === 'client') {
        $owners = mysqli_query($conn, "SELECT id FROM users WHERE role='owner'");
        while ($o = mysqli_fetch_assoc($owners)) {
            insertNotification($conn, (int)$o['id'],
                "Client Baru Terdaftar",
                "Admin mendaftarkan client baru: {$name} ({$email})."
            );
        }
    }

    mysqli_commit($conn);
    redirectWithMessage("index.php", "success",
        "Pengguna <strong>$name</strong> berhasil ditambahkan."
    );

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['user_errors'] = ["Kesalahan sistem: " . $e->getMessage()];
    header("Location: add.php");
    exit();
}