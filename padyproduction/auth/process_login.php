<?php

session_start();

require_once("../config/database.php");

$username = $_POST['username'];
$password = $_POST['password'];

$query = mysqli_query(
    $conn,
    "SELECT * FROM users WHERE username='$username'"
);

$user = mysqli_fetch_assoc($query);

if ($user) {

    if ($password == $user['password']) {

        $_SESSION['id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'admin') {
            header("Location: ../admin/dashboard.php");
        }

        elseif ($user['role'] == 'owner') {
            header("Location: ../owner/dashboard.php");
        }

        elseif ($user['role'] == 'crew') {
            header("Location: ../crew/dashboard.php");
        }

        else {
            header("Location: ../user/dashboard.php");
        }

        exit();
    }
}

echo "Username atau Password Salah";