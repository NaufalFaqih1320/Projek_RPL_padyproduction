<?php

/**
 * config/auth.php
 * Memastikan sesi aktif dan user sudah login.
 * Panggil file ini di awal setiap halaman yang butuh autentikasi.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika belum login, redirect ke halaman login
if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
    // Hitung kedalaman relatif dari root /padyproduction/
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $depth  = substr_count($script, '/padyproduction/') > 0
        ? substr_count(explode('/padyproduction/', $script)[1], '/') 
        : 1;
    $prefix = str_repeat('../', $depth);
    header("Location: {$prefix}auth/login.php");
    exit();
}
