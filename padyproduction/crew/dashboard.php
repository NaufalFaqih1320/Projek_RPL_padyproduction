<?php

require_once("../config/auth.php");

if ($_SESSION['role'] != 'crew') {
    header("Location: ../auth/login.php");
    exit();
}

echo "<h1>Dashboard Crew</h1>";
echo "Selamat datang, " . $_SESSION['name'];