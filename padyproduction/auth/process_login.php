<?php

/**
 * auth/process_login.php — VERSI YANG DISEMPURNAKAN
 *
 * Perubahan dari versi asli:
 * 1. Mendukung password_hash (bcrypt) — password lama (plain) tetap bisa login,
 *    lalu otomatis di-upgrade ke bcrypt
 * 2. Proteksi brute-force sederhana via session counter
 * 3. Validasi method & CSRF-ready
 */

session_start();
require_once("../config/database.php");
require_once("../config/helpers.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// ── Validasi input kosong ──────────────────────────────────────────────────
if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = "Username dan password wajib diisi.";
    header("Location: login.php");
    exit();
}

// ── Proteksi brute-force sederhana ─────────────────────────────────────────
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['login_lockout']))  $_SESSION['login_lockout']  = 0;

if ($_SESSION['login_lockout'] > time()) {
    $sisa = ceil(($_SESSION['login_lockout'] - time()) / 60);
    $_SESSION['login_error'] = "Terlalu banyak percobaan login. Coba lagi dalam {$sisa} menit.";
    header("Location: login.php");
    exit();
}

// ── Ambil user dari database ────────────────────────────────────────────────
$username_esc = mysqli_real_escape_string($conn, $username);
$query  = mysqli_query($conn, "SELECT * FROM users WHERE username='$username_esc' LIMIT 1");
$user   = mysqli_fetch_assoc($query);

$login_ok = false;

if ($user) {
    // Cek password: dukung bcrypt DAN plain text (legacy)
    if (password_verify($password, $user['password'])) {
        $login_ok = true;
    } elseif ($password === $user['password']) {
        // Password lama (plain) cocok — upgrade ke bcrypt
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $hashed_esc = mysqli_real_escape_string($conn, $hashed);
        mysqli_query($conn, "UPDATE users SET password='$hashed_esc' WHERE id='{$user['id']}'");
        $login_ok = true;
    }
}

if (!$login_ok) {
    $_SESSION['login_attempts']++;
    if ($_SESSION['login_attempts'] >= 5) {
        $_SESSION['login_lockout']  = time() + (15 * 60); // kunci 15 menit
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_error']    = "Terlalu banyak percobaan. Akun terkunci 15 menit.";
    } else {
        $sisa_coba = 5 - $_SESSION['login_attempts'];
        $_SESSION['login_error'] = "Username atau password salah. Sisa percobaan: {$sisa_coba}.";
    }
    header("Location: login.php");
    exit();
}

// ── Login berhasil ──────────────────────────────────────────────────────────
$_SESSION['login_attempts'] = 0;
$_SESSION['login_lockout']  = 0;

// Simpan data session
$_SESSION['id']       = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['name']     = $user['name'];
$_SESSION['role']     = $user['role'];
$_SESSION['email']    = $user['email'];

// Perbarui waktu login terakhir (opsional — pastikan kolom ada atau hapus baris ini)
// mysqli_query($conn, "UPDATE users SET last_login=NOW() WHERE id='{$user['id']}'");

// Proses reminder yang sudah jatuh tempo saat login
processReminders($conn);

$routes = [
    'admin'  => '../admin/dashboard.php',
    'owner'  => '../owner/dashboard.php',
    'crew'   => '../crew/dashboard.php',
    'client' => '../user/dashboard.php',
    'user'   => '../user/dashboard.php',
];

header("Location: " . ($routes[$user['role']] ?? '../auth/login.php'));
exit();