<?php

require_once("../config/auth.php");

if ($_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

echo "<h1>Dashboard Admin</h1>";
echo "Selamat datang, " . $_SESSION['name'];