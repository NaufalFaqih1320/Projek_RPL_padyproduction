<?php
session_start();

echo "<h1>Dashboard User</h1>";
echo "Selamat datang " . $_SESSION['username'];