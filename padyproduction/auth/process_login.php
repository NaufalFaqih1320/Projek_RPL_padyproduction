<?php

session_start();
require_once("../config/database.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$username = mysqli_real_escape_string($conn, trim($_POST['username']));
$password = trim($_POST['password']);

if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = "Username dan password wajib diisi.";
    header("Location: login.php");
    exit();
}

$query  = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' LIMIT 1");
$user   = mysqli_fetch_assoc($query);

if ($user && $password === $user['password']) {
    $_SESSION['id']       = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['name']     = $user['name'];
    $_SESSION['role']     = $user['role'];
    $_SESSION['email']    = $user['email'];

    $routes = [
        'admin'  => '../admin/dashboard.php',
        'owner'  => '../owner/dashboard.php',
        'crew'   => '../crew/dashboard.php',
        'client' => '../user/dashboard.php',
    ];

    header("Location: " . ($routes[$user['role']] ?? '../auth/login.php'));
    exit();
}

$_SESSION['login_error'] = "Username atau password salah.";
header("Location: login.php");
exit();