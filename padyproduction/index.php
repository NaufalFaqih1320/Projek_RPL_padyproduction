<?php

session_start();

if (isset($_SESSION['id'])) {
    $routes = [
        'admin'  => 'admin/dashboard.php',
        'owner'  => 'owner/dashboard.php',
        'crew'   => 'crew/dashboard.php',
        'client' => 'user/dashboard.php',
    ];
    header("Location: " . ($routes[$_SESSION['role']] ?? 'auth/login.php'));
} else {
    header("Location: auth/login.php");
}
exit();
