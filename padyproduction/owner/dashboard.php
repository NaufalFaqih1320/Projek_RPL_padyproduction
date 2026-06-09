<?php

require_once("../config/auth.php");

if ($_SESSION['role'] != 'owner') {
    header("Location: ../auth/login.php");
    exit();
}

echo "<h1>Dashboard Owner</h1>";
echo "Selamat datang, " . $_SESSION['name'];